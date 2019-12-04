{if isset($cluster.showMoreFacetPopup) && $cluster.showMoreFacetPopup}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		<div class="facetValue">
			<label for="{$title}_{$thisFacet.value|escapeCSS}">
				<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="{$title}_{$thisFacet.value|escapeCSS}" id="{$title}_{$thisFacet.value|escapeCSS}" onclick="document.location = '{if $thisFacet.isApplied}{$thisFacet.removalUrl|escape}{else}{$thisFacet.url|escape}{/if}';">
				{$thisFacet.display}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}
			</label>
		</div>
	{/foreach}
	{* Show more facet popup list *}
	<div class="facetValue" id="more{$title}"><a href="#" onclick="AspenDiscovery.ResultsList.multiSelectMoreFacetPopup('More {$cluster.label}{if substr($cluster.label, -1) != 's'}s{/if}', '{$title}'); return false;">{translate text='more'} ...</a></div>
	<div id="moreFacetPopup_{$title}" style="display:none">
		<p>{translate text="more_facet_popup_descriptions" defaultText="Please select one of the items below to narrow your search by %1%." 1=$cluster.label}</p>
		<form id="facetPopup_{$title|escapeCSS}" onsubmit="return AspenDiscovery.ResultsList.processMultiSelectMoreFacetForm('#facetPopup_{$title|escapeCSS}', '{$cluster.field_name}');">
			<div class="container-12">
				<div class="row">
					{foreach from=$cluster.sortedList item=thisFacet name="narrowLoop"}
						<div class="col-sm-6">
							<label>
								<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="filter[]" value='{$cluster.field_name}:{if empty($thisFacet.value)}(""){else}"{$thisFacet.value}"{/if}'>
								{$thisFacet.display}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}
							</label>
						</div>
					{/foreach}
				</div>
			</div>
		</form>
	</div>
{else}
	{* Simple list with more link to show remaining values (if any) *}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		{if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
		{* Show More link*}
			<div class="facetValue" id="more{$title}"><a href="#" onclick="AspenDiscovery.ResultsList.moreFacets('{$title}'); return false;">{translate text='more'} ...</a></div>
		{* Start div for hidden content*}
			<div class="narrowGroupHidden" id="narrowGroupHidden_{$title}" style="display:none">
		{/if}
		<div class="facetValue">
			<label for="{$title}_{$thisFacet.value|escapeCSS}">
				<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="{$title}_{$thisFacet.value|escapeCSS}" id="{$title}_{$thisFacet.value|escapeCSS}" onclick="document.location = '{if $thisFacet.isApplied}{$thisFacet.removalUrl|escape}{else}{$thisFacet.url|escape}{/if}';">
				{$thisFacet.display}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}
			</label>
		</div>
	{/foreach}
	{if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}
		<div class="facetValue">
			<a href="#" onclick="AspenDiscovery.ResultsList.lessFacets('{$title}'); return false;">{translate text='less'} ...</a>
		</div>
		</div>{* closes hidden div *}
	{/if}
{/if}