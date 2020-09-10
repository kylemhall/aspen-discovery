{strip}
<div id="header-menu" class="dropdownMenu" style="display: none">
	{if !empty($userPermissions)}
		<div id="home-page-home-button" class="header-menu-option">
			<a href="/Admin/Home">
				<i class="fas fa-tools"></i>{translate text='Aspen Administration'}
			</a>
		</div>
	{/if}

	{if !empty($homeLink)}
		<a href="{$homeLink}">
			<div id="home-page-home-button" class="header-menu-option">
				<i class="fas fa-landmark"></i>{translate text='Library Home Page'}
			</div>
		</a>
	{/if}

	{if $showLibraryHoursAndLocationsLink}
		<a href="/AJAX/JSON?method=getHoursAndLocations" data-title="{translate text="Library Hours and Locations" inAttribute=true}" class="modalDialogTrigger">
			<div id="home-page-hours-locations" class="header-menu-option">
				<i class="fas fa-map-marker-alt"></i>
				{if $numLocations == 1}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours &amp; Location"}
					{else}
						{translate text="Location"}
					{/if}
				{else}
					{if !isset($hasValidHours) || $hasValidHours}
						{translate text="Library Hours &amp; Location"}
					{else}
						{translate text="Locations"}
					{/if}
				{/if}
			</div>
		</a>
	{/if}

	{if $libraryLinks}
		{foreach from=$libraryLinks item=linkCategory key=categoryName name=linkLoop}
			{if $categoryName && !preg_match('/none-\\d+/', $categoryName)}
				{* Put the links within a collapsible section *}
				<a onclick="return AspenDiscovery.toggleMenuSection('{$categoryName|escapeCSS}');">
					<div class="header-menu-section" id="{$categoryName|escapeCSS}MenuSection">
						<i class="fas {if !array_key_exists($categoryName, $expandedLinkCategories)}fa-caret-right{else}fa-caret-down{/if}"></i>{$categoryName}
					</div>
				</a>
				<div id="{$categoryName|escapeCSS}MenuSectionBody" class="menuSectionBody" {if !array_key_exists($categoryName, $expandedLinkCategories)}style="display: none" {/if}>
					{foreach from=$linkCategory item=link key=linkName}
						{if $link->htmlContents}
							{$link->htmlContents}
						{else}
							<div class="header-menu-option {if $categoryName && !preg_match('/none-\\d+/', $categoryName)}childMenuItem{/if}">
								<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if}>
									{$linkName}
								</a>
							</div>
						{/if}
					{/foreach}
				</div>
			{else}
				{* No category name, display these links as buttons *}
				{foreach from=$linkCategory item=link key=linkName}
					{if $link->htmlContents}
						{$link->htmlContents}
					{else}
						<a href="{$link->url}" {if $link->openInNewTab}target="_blank"{/if}>
							<div class="header-menu-option">
								{$linkName}
							</div>
						</a>
					{/if}
				{/foreach}
			{/if}
		{/foreach}
	{/if}

	{if !empty($validLanguages)}
		<div class="header-menu-section" id="aspenLanguagesMenuSection">
			<i class="fas fa-globe"></i>{translate text="Language"}
		</div>
		{foreach from=$validLanguages key=languageCode item=language}
			{if $userLang->code!=$languageCode}
			<a onclick="return AspenDiscovery.setLanguage('{$languageCode}')">
			{/if}
				<div class="header-menu-option">
					{if $userLang->code==$languageCode}
						<i class="fas fa-check"></i>
					{/if}
					&nbsp;&nbsp;{$language->displayName}
				</div>
			{if $userLang->code!=$languageCode}
			</a>
			{/if}
		{/foreach}
	{/if}

	{if $masqueradeMode}
		<a class="btn btn-default btn-sm btn-block" onclick="AspenDiscovery.Account.endMasquerade()">{translate text="End Masquerade"}</a>
	{/if}

	<a href="/MyAccount/Logout" id="logoutLink" title="{translate text="Sign Out"}" class="btn btn-default btn-sm btn-block" {if !$loggedIn}style="display:none"{/if}>
		{translate text="Sign Out"}
	</a>
</div>
{/strip}