{strip}
	{if !$masqueradeMode || ($masqueradeMode && $allowReadingHistoryDisplayInMasqueradeMode)}
		{* Do not display Reading History in Masquerade Mode, unless the library has allowed it *}
		<form id="readingListForm" action="/MyAccount/ReadingHistory" class="form-inline">

			{* Reading History Actions *}
			<div class="row">
				<input type="hidden" name="page" value="{$page}">
				<input type="hidden" name="patronId" value="{$selectedUser}">
				<input type="hidden" name="readingHistoryAction" id="readingHistoryAction" value="">
				<div id="readingListActionsTop" class="col-xs-6">
					<div class="btn-group btn-group-sm">
						{if $historyActive == true}
							<button class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.ReadingHistory.exportListAction()">{translate text="Export To Excel"}</button>
							{if $transList}
								<button class="btn btn-sm btn-warning" onclick="return AspenDiscovery.Account.ReadingHistory.deletedMarkedAction()">{translate text="Delete Marked"}</button>
							{/if}
						{else}
							<button class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.ReadingHistory.optInAction()">{translate text="Start Recording My Reading History"}</button>
						{/if}
					</div>
				</div>
				{if $historyActive == true}
					<div class="col-xs-6">
						<div class="btn-group btn-group-sm pull-right">
							{if $transList}
								<button class="btn btn-sm btn-danger " onclick="return AspenDiscovery.Account.ReadingHistory.deleteAllAction()">{translate text="Delete All"}</button>
							{/if}
							<button class="btn btn-sm btn-danger" onclick="return AspenDiscovery.Account.ReadingHistory.optOutAction()">{translate text="Stop Recording My Reading History"}</button>
						</div>
					</div>
				{/if}

				<hr>

				{if $transList}
					{* Results Page Options *}
					<div id="pager" class="col-xs-12">
						<div class="row">
							<div class="form-group col-sm-3" id="sortOptions">
								<label for="sortMethod" class="control-label">&nbsp;</label>
								<select aria-label="{translate text="Sort By" inAttribute=true}" class="sortMethod form-control" id="sortMethod" name="accountSort" onchange="return AspenDiscovery.Account.loadReadingHistory($('#readingListForm input[name=patronId]').val(),$('#sortMethod option:selected').val(), 1,!$('#hideCovers').is(':checked'))">
									{foreach from=$sortOptions item=sortOptionLabel key=sortOption}
										<option value="{$sortOption}" {if $sortOption == $defaultSortOption}selected="selected"{/if}>{translate text="Sort By %1%" 1=$sortOptionLabel}</option>
									{/foreach}
								</select>
							</div>
							<div class="form-group col-sm-7" id="recordsPerPage">
								<form class="form-inline">
									<div class="input-group">
										<input aria-label="{translate text="Filter Reading History" inAttribute=true}" type="text" class="form-control" name="readingHistoryFilter" id="readingHistoryFilter" value="{$readingHistoryFilter}"/>
										<span class="input-group-btn">
											<button type="submit" class="btn btn-default" onclick="return AspenDiscovery.Account.loadReadingHistory($('#readingListForm input[name=patronId]').val(),$('#sortMethod option:selected').val(), 1,!$(this).is(':checked'), $('#readingHistoryFilter').val())">Filter</button>
										</span>
									</div>
								</form>
							</div>

							<div class="form-group col-sm-2" id="coverOptions">
								<label for="hideCovers" class="control-label checkbox pull-right"> {translate text='Hide Covers'} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.loadReadingHistory($('#readingListForm input[name=patronId]').val(),$('#sortMethod option:selected').val(), {$curPage},!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
							</div>
						</div>
					</div>

					{* Reading History Entries *}
					<div class="striped">
						{foreach from=$transList item=record name="recordLoop" key=recordKey}
							{include file="MyAccount/readingHistoryEntry.tpl" record=$record}
						{/foreach}
					</div>
					<hr>
					<div class="row">
						<div class="col-xs-12">
							<div id="readingListActionsBottom" class="btn-group btn-group-sm">
								{if $historyActive == true}
									<button class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.ReadingHistory.exportListAction()">{translate text="Export To Excel"}</button>
									{if $transList}
										<button class="btn btn-sm btn-warning" onclick="return AspenDiscovery.Account.ReadingHistory.deletedMarkedAction()">{translate text="Delete Marked"}</button>
									{/if}
								{else}
									<button class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.ReadingHistory.optInAction()">{translate text="Start Recording My Reading History"}</button>
								{/if}
							</div>
						</div>
					</div>
					{if $pageLinks.all}
						<div class="text-center">{$pageLinks.all}</div>{/if}
				{elseif $historyActive == true}
					{* No Items in the history, but the history is active *}
					{translate text="empty_reading_history" defaultText="You do not have any items in your reading list.    It may take up to 3 hours for your reading history to be updated after you start recording your history."}
				{/if}
			</div>
		</form>
	{/if}
{/strip}
<script type="text/javascript">
	AspenDiscovery.Ratings.initializeRaters();
</script>