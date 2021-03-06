{strip}
<div id="page-content" class="content">
<form name="placeHoldForm" id="placeHoldForm" method="post" class="form">
		<input type="hidden" name="id" id="id" value="{$id}">
		<input type="hidden" name="recordSource" id="recordSource" value="{$recordSource}">
		<input type="hidden" name="module" id="module" value="{$activeRecordProfileModule}">
		{if $volume}
			<input type="hidden" name="volume" id="volume" value="{$volume}">
		{/if}
		<fieldset>
			<div class="holdsSummary">
				<input type="hidden" name="holdCount" id="holdCount" value="1">
				<div class="alert alert-warning" id="overHoldCountWarning" {if !$showOverHoldLimit}style="display:none"{/if}>Warning: You have reached the maximum of <span class='maxHolds'>{$maxHolds}</span> holds for your account.  You must cancel a hold before you can place a hold on this title.</div>
				<div id="holdError" class="pageWarning" style="display: none"></div>
			</div>

			<p class="alert alert-info">
				{/strip}
				Hold Notification is sent once your hold arrives at the pickup location. Your hold must be picked up within 5 days.
				{strip}
			</p>

			{* Responsive theme enforces that the user is always logged in before getting here*}
			<div id="holdOptions">
				<div id="pickupLocationOptions" class="form-group">
					<label class="control-label" for="pickupBranch">{translate text="I want to pick this up at"} </label>
					<div class="controls">
						<select name="pickupBranch" id="pickupBranch" class="form-control">
							{if count($pickupLocations) > 0}
								{foreach from=$pickupLocations item=location}
									{if is_string($location)}
										<option value="undefined">{$location}</option>
									{else}
										<option value="{$location->code}"{if is_object($location) && ($location->getSelected() == "selected")} selected="selected"{/if}
										        data-users="[{$location->pickupUsers|@implode:','}]">{$location->displayName}</option>
									{/if}
								{/foreach}
							{else}
								<option>placeholder</option>
							{/if}
						</select>
					</div>
				</div>
					{*<div id="userOption" class="form-group"{*if count($pickupLocations[0]->pickupUsers) < 2} style="display: none"{/if* }>{* display if the first location will need a user selected*}
					<div id="userOption" class="form-group"{if !$multipleUsers} style="display: none"{/if}>{* display if there are multiple accounts *}
						<label for="user" class="control-label">{translate text="Place hold for the chosen location using account"}: </label>
						<div class="controls">
							<select name="user" id="user" class="form-control">
								{* Built by jQuery below *}
							</select>
						</div>
					</div>
					<script type="text/javascript">
						{literal}
						$(function(){
							let userNames = {
							{/literal}
							{$activeUserId}: "{$userDisplayName|escape:javascript} - {$user->getHomeLibrarySystemName()}",
							{assign var="linkedUsers" value=$user->getLinkedUsers()}
							{foreach from="$linkedUsers" item="tron"}
								{$tron->id}: "{$tron->displayName|escape:javascript} - {$tron->getHomeLibrarySystemName()}",
							{/foreach}
							{literal}
								};
							$('#pickupBranch').change(function(){
								let users = $('option:selected', this).data('users');
								let options = '';
								$.each(users, function(indexIgnored,userId){
										options += '<option value="'+userId+'">'+userNames[userId]+'</option>'
									});
									$('#userOption select').html(options);
							}).change(); /* trigger on initial load */
						});
						{/literal}
						{* /* when hiding single account pick-up locations */
						if (Array.isArray(users) && users.length > 1) {
								$.each(users, function(indexIgnored,userId){
										options += '<option value="'+userId+'">'+userNames[userId]+'</option>'
									});
									$('#userOption select').html(options);
									$('#userOption').fadeIn();
								} else {
									/* Should be only one user in users; Hide and set to the appropriate user */
									$('#userOption').fadeOut(function(){
										$('#userOption select').html('<option selected value="'+users[0] +'" />');
									})
								}

						 *}
					</script>
				{if $showHoldCancelDate == 1}
					<div id="cancelHoldDate" class="form-group">
						<label class="control-label" for="cancelDate">{translate text="Automatically cancel this hold if not filled by"}:</label>
						<div class="input-group input-append date controls" id="cancelDatePicker">
							{* TODO: defaultNotNeeded not implemented yet. plb 4-1-2015 *}
							{* data-provide attribute loads the datepicker through bootstrap data api *}
							{* start date sets minimum. date sets initial value: days from today, eg +8d is 8 days from now. *}
							<input type="text" name="cancelDate" id="cancelDate" placeholder="mm/dd/yyyy" class="form-control" size="10" {*if $defaultNotNeededAfterDays}value="{$defaultNotNeededAfterDays}"{/if*}
							       data-provide="datepicker" data-date-format="mm/dd/yyyy" data-date-start-date="0d"{*if $defaultNotNeededAfterDays} data-date="+{$defaultNotNeededAfterDays}d"{/if*}>
							<span class="input-group-addon"><span class="glyphicon glyphicon-calendar" onclick="$('#cancelDate').focus().datepicker('show')" aria-hidden="true"></span></span>
						</div>
						<div class="loginFormRow">
							<i>{translate text="automatic_cancellation_notice"}</i>
						</div>
					</div>
				{/if}
				{if count($holdDisclaimers) > 0}
					{foreach from=$holdDisclaimers item=holdDisclaimer key=library}
						<div class="holdDisclaimer alert alert-warning">
							{if count($holdDisclaimers) > 1}<div class="holdDisclaimerLibrary">{$library}</div>{/if}
							{$holdDisclaimer}
						</div>
					{/foreach}
				{/if}
				<br>
				<div class="form-group">
					<label for="autologout" class="checkbox"><input type="checkbox" name="autologout" id="autologout" {if $isOpac == true}checked="checked"{/if}> {translate text="Log me out after requesting the item."}</label>
					<input type="hidden" name="holdType" value="hold">
				</div>
			</div>
		</fieldset>
	</form>
</div>
{/strip}
