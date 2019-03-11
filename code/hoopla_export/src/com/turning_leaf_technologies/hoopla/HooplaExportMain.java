package com.turning_leaf_technologies.hoopla;

import com.turning_leaf_technologies.config.ConfigUtil;
import com.turning_leaf_technologies.logging.LoggingUtil;
import com.turning_leaf_technologies.net.NetworkUtils;
import com.turning_leaf_technologies.net.URLPostResponse;
import com.turning_leaf_technologies.strings.StringUtils;
import org.apache.commons.codec.binary.Base64;
import org.apache.logging.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Date;

public class HooplaExportMain {
	private static Logger logger;

	private static Ini configIni;
	private static String hooplaAPIBaseURL;

	private static Long lastExportTime;
	private static Long lastExportTimeVariableId;
	private static boolean hadErrors = false;

	//Reporting information
	private static long exportLogId;
	private static PreparedStatement addNoteToExportLogStmt;
	public static void main(String[] args){
		String serverName = args[0];
		args = Arrays.copyOfRange(args, 1, args.length);
		boolean doFullReload = false;
		if (args.length == 1){
			//Check to see if we got a full reload parameter
			String firstArg = args[0].replaceAll("\\s", "");
			if (firstArg.matches("^fullReload(=true|1)?$")){
				doFullReload = true;
			}
		}

		Date startTime = new Date();
		logger = LoggingUtil.setupLogging(serverName, "hoopla_export");
		logger.info(startTime.toString() + ": Starting Hoopla Export");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		configIni = ConfigUtil.loadConfigFile("config.ini", serverName, logger);

		//Connect to the Aspen database
		Connection aspenConn = null;
		try{
			String databaseConnectionInfo = ConfigUtil.cleanIniValue(configIni.get("Database", "database_aspen_jdbc"));
			aspenConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to Aspen database " + e.toString());
			System.exit(1);
		}

		//Start a log entry
		createDbLogEntry(startTime, aspenConn);

		//Get the last grouping time
		loadLastGroupingTime(aspenConn);

		//Do work here
		exportHooplaData(aspenConn, lastExportTime, doFullReload);

		if (!hadErrors){
			updateExportTime(aspenConn, startTime.getTime() / 1000);
		}

		logger.info("Finished exporting data " + new Date().toString());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - startTime.getTime();
		logger.info("Elapsed Minutes " + (elapsedTime / 60000));

		try {
			PreparedStatement finishedStatement = aspenConn.prepareStatement("UPDATE hoopla_export_log SET endTime = ? WHERE id = ?");
			finishedStatement.setLong(1, endTime / 1000);
			finishedStatement.setLong(2, exportLogId);
			finishedStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update hoopla export log with completion time.", e);
		}

		try{
			aspenConn.close();
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
	}

	private static void loadLastGroupingTime(Connection aspenConn) {
		try{
			PreparedStatement loadLastGroupingTime = aspenConn.prepareStatement("SELECT * from variables WHERE name = 'lastHooplaExport'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastExportTimeVariableId = lastGroupingTimeRS.getLong("id");
				try {
					lastExportTime = lastGroupingTimeRS.getLong("value");
				}catch (Exception e){
					//Initially this is set to false, so we get an error.  If that happens, just set lastExport time to null
					lastExportTime = null;
				}

			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logger.error("Error loading last hoopla export time", e);
			addNoteToExportLog("Error loading last hoopla export time " + e.toString());
			System.exit(1);
		}
	}

	private static void createDbLogEntry(Date startTime, Connection aspenConn) {
		try {
			logger.info("Creating log entry for index");
			PreparedStatement createLogEntryStatement = aspenConn.prepareStatement("INSERT INTO hoopla_export_log (startTime, lastUpdate, notes) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			createLogEntryStatement.setLong(1, startTime.getTime() / 1000);
			createLogEntryStatement.setLong(2, startTime.getTime() / 1000);
			createLogEntryStatement.setString(3, "Initialization complete");
			createLogEntryStatement.executeUpdate();
			ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
			if (generatedKeys.next()){
				exportLogId = generatedKeys.getLong(1);
			}

			addNoteToExportLogStmt = aspenConn.prepareStatement("UPDATE hoopla_export_log SET notes = ?, lastUpdate = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to create log entry for record grouping process", e);
			System.exit(0);
		}
	}

	private static void exportHooplaData(Connection aspenConn, Long startTime, boolean doFullReload) {
		try{
			//Find a library id to get data from
			String hooplaLibraryId = getHooplaLibraryId(aspenConn);
			if (hooplaLibraryId == null){
				logger.error("No hoopla library id found");
				addNoteToExportLog("No hoopla library id found");
				hadErrors = true;
				return;
			}else{
				addNoteToExportLog("Hoopla library id is " + hooplaLibraryId);
			}

			String accessToken = getAccessToken();
			if (accessToken == null){
				hadErrors = true;
				return;
			}

			//Formulate the first call depending on if we are doing a full reload or not
			String url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content";
			if (!doFullReload && startTime != null){
				url += "?startTime=" + startTime;
			}

			int numProcessed = 0;
			URLPostResponse response = NetworkUtils.getURL(url, logger, accessToken);
			JSONObject responseJSON = new JSONObject(response.getMessage());
			if (responseJSON.has("titles")){
				JSONArray responseTitles = responseJSON.getJSONArray("titles");
				if (responseTitles != null && responseTitles.length() > 0){
					numProcessed += updateTitlesInDB(aspenConn, responseTitles);
				}

				String startToken = null;
				if (responseJSON.has("nextStartToken")){
					startToken = responseJSON.getString("nextStartToken");
				}

				//TODO: Determine if the encoding is needed
				String encodedToken = Base64.encodeBase64String(accessToken.getBytes());
				while (startToken != null){
					url = hooplaAPIBaseURL + "/api/v1/libraries/" + hooplaLibraryId + "/content?startToken=" + startToken;
					response = NetworkUtils.getURL(url, logger, accessToken);
					responseJSON = new JSONObject(response.getMessage());
					if (responseJSON.has("titles")) {
						responseTitles = responseJSON.getJSONArray("titles");
						if (responseTitles != null && responseTitles.length() > 0) {
							numProcessed += updateTitlesInDB(aspenConn, responseTitles);
						}
					}
					if (responseJSON.has("nextStartToken")) {
						startToken = responseJSON.getString("nextStartToken");
					} else {
						startToken = null;
					}
					if (numProcessed % 10000 == 0){
						addNoteToExportLog("Processed " + numProcessed + " records from hoopla");
					}
				}
			}
		}catch (Exception e){
			logger.error("Error exporting hoopla data", e);
			addNoteToExportLog("Error exporting hoopla data " + e.toString());
			hadErrors = true;
		}
	}

	private static PreparedStatement updateHooplaTitleInDB = null;
	private static int updateTitlesInDB(Connection aspenConn, JSONArray responseTitles) {
		int numUpdates = 0;
		try {
			if (updateHooplaTitleInDB == null) {
				updateHooplaTitleInDB = aspenConn.prepareStatement("INSERT INTO hoopla_export (hooplaId, active, title, kind, pa, demo, profanity, rating, abridged, children, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY " +
								"UPDATE active = VALUES(active), title = VALUES(title), kind = VALUES(kind), pa = VALUES(pa), demo = VALUES(demo), profanity = VALUES(profanity), " +
								"rating = VALUES(rating), abridged = VALUES(abridged), children = VALUES(children), price = VALUES(price)");
			}
			for (int i = 0; i < responseTitles.length(); i++){
				JSONObject curTitle = responseTitles.getJSONObject(i);
				updateHooplaTitleInDB.setLong(1, curTitle.getLong("titleId"));
				updateHooplaTitleInDB.setBoolean(2, curTitle.getBoolean("active"));
				updateHooplaTitleInDB.setString(3, curTitle.getString("title"));
				updateHooplaTitleInDB.setString(4, curTitle.getString("kind"));
				updateHooplaTitleInDB.setBoolean(5, curTitle.getBoolean("pa"));
				updateHooplaTitleInDB.setBoolean(6, curTitle.getBoolean("demo"));
				updateHooplaTitleInDB.setBoolean(7, curTitle.getBoolean("profanity"));
				updateHooplaTitleInDB.setString(8, curTitle.has("rating") ? curTitle.getString("rating") : "");
				updateHooplaTitleInDB.setBoolean(9, curTitle.getBoolean("abridged"));
				updateHooplaTitleInDB.setBoolean(10, curTitle.getBoolean("children"));
				updateHooplaTitleInDB.setDouble(11, curTitle.getDouble("price"));
				updateHooplaTitleInDB.executeUpdate();
				numUpdates++;
			}

		}catch (Exception e){
			logger.error("Error updating hoopla data in database", e);
			addNoteToExportLog("Error updating hoopla data in database " + e.toString());
			hadErrors = true;
		}
		return numUpdates;
	}

	private static String getAccessToken() {
		String hooplaUsername = ConfigUtil.cleanIniValue(configIni.get("Hoopla", "HooplaAPIUser"));
		String hooplaPassword = ConfigUtil.cleanIniValue(configIni.get("Hoopla", "HooplaAPIpassword"));
		if (hooplaUsername == null || hooplaPassword == null){
			logger.error("Please set HooplaAPIUser and HooplaAPIpassword in config.pwd.ini");
			addNoteToExportLog("Please set HooplaAPIUser and HooplaAPIpassword in config.pwd.ini");
			return null;
		}
		hooplaAPIBaseURL = ConfigUtil.cleanIniValue(configIni.get("Hoopla", "APIBaseURL"));
		if (hooplaAPIBaseURL == null){
			hooplaAPIBaseURL = "https://hoopla-api-dev.hoopladigital.com";
		}
		String getTokenUrl = hooplaAPIBaseURL + "/v2/token";
		URLPostResponse response = NetworkUtils.postToURL(getTokenUrl, null, "application/json", null, logger, hooplaUsername + ":" + hooplaPassword);
		if (response.isSuccess()){
			try {
				JSONObject responseJSON = new JSONObject(response.getMessage());
				return responseJSON.getString("access_token");
			} catch (JSONException e) {
				addNoteToExportLog("Could not parse JSON for token " + response.getMessage());
				logger.error("Could not parse JSON for token " + response.getMessage(), e);
				return null;
			}
		}else{
			addNoteToExportLog("Please set HooplaAPIUser and HooplaAPIpassword in config.pwd.ini");
			return null;
		}
	}

	/**
	 * Retrieves the hoopla library id.  Since all libraries have the same content,
	 * we can simply use the first id rather than processing them all individually.
	 */
	private static String getHooplaLibraryId(Connection aspenConn) throws SQLException {
		PreparedStatement getLibraryIdStmt = aspenConn.prepareStatement("SELECT hooplaLibraryID from library where hooplaLibraryID is not null and hooplaLibraryID != 0 LIMIT 1");
		ResultSet getLibraryIdRS = getLibraryIdStmt.executeQuery();
		if (getLibraryIdRS.next()){
			return getLibraryIdRS.getString("hooplaLibraryID");
		}else{
			return null;
		}
	}

	private static void updateExportTime(Connection aspenConn, long startTime) {
		//Update the last grouping time in the variables table
		try{
			if (lastExportTimeVariableId != null){
				PreparedStatement updateVariableStmt  = aspenConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, startTime);
				updateVariableStmt.setLong(2, lastExportTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = aspenConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('lastHooplaExport', ?)");
				insertVariableStmt.setLong(1, startTime);
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error setting last grouping time", e);
		}
	}

	private static StringBuffer notes = new StringBuffer();
	private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	private static void addNoteToExportLog(String note) {
		try {
			Date date = new Date();
			notes.append("<br>").append(dateFormat.format(date)).append(": ").append(note);
			addNoteToExportLogStmt.setString(1, StringUtils.trimTo(65535, notes.toString()));
			addNoteToExportLogStmt.setLong(2, new Date().getTime() / 1000);
			addNoteToExportLogStmt.setLong(3, exportLogId);
			addNoteToExportLogStmt.executeUpdate();
			logger.info(note);
		} catch (SQLException e) {
			logger.error("Error adding note to Export Log", e);
		}
	}
}