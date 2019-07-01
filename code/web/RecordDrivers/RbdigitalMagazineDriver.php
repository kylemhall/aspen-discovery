<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/Rbdigital/RbdigitalMagazine.php';
class RbdigitalMagazineDriver extends GroupedWorkSubDriver {
    private $id;
    /** @var RbdigitalMagazine */
    private $rbdigitalProduct;
    private $rbdigitalRawMetadata;
    private $valid;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($recordId, $groupedWork = null) {
        $this->id = $recordId;

        $this->rbdigitalProduct = new RbdigitalMagazine();
        $this->rbdigitalProduct->magazineId = $recordId;
        if ($this->rbdigitalProduct->find(true)) {
            $this->valid = true;
            $this->rbdigitalRawMetadata = json_decode($this->rbdigitalProduct->rawResponse);
        } else {
            $this->valid = false;
            $this->rbdigitalProduct = null;
        }
        if ($this->valid){
            parent::__construct($groupedWork);
        }
    }

    public function getIdWithSource(){
        return 'rbdigital_magazine:' . $this->id;
    }

    /**
     * Load the grouped work that this record is connected to.
     */
    public function loadGroupedWork() {
    	if ($this->groupedWork == null){
		    require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
		    require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		    $groupedWork = new GroupedWork();
		    $query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='rbdigital_magazine' AND identifier = '" . $this->getUniqueID() . "'";
		    $groupedWork->query($query);

		    if ($groupedWork->N == 1){
			    $groupedWork->fetch();
			    $this->groupedWork = clone $groupedWork;
		    }
	    }
    }

    public function getRbdigitalBookcoverUrl()
    {
        $images = $this->rbdigitalRawMetadata->images;
        foreach ($images as $image) {
            return $image->url;
        }
        return null;
    }

    public function getModule()
    {
        return 'RbdigitalMagazine';
    }

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display the full record information on the Staff
     * View tab of the record view page.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getStaffView()
    {
        global $interface;
        $groupedWorkDetails = $this->getGroupedWorkDriver()->getGroupedWorkDetails();
        $interface->assign('groupedWorkDetails', $groupedWorkDetails);

        $interface->assign('rbdigitalExtract', $this->rbdigitalRawMetadata);
        return 'RecordDrivers/Rbdigital/staff-view.tpl';
    }

    /**
     * Get the full title of the record.
     *
     * @return  string
     */
    public function getTitle()
    {
        $title = $this->rbdigitalProduct->title;
        return $title;
    }

    /**
     * The Table of Contents extracted from the record.
     * Returns null if no Table of Contents is available.
     *
     * @access  public
     * @return  array              Array of elements in the table of contents
     */
    public function getTableOfContents()
    {
        // TODO: Implement getTableOfContents() method.
        return array();
    }

    /**
     * Return the unique identifier of this record within the Solr index;
     * useful for retrieving additional information (like tags and user
     * comments) from the external MySQL database.
     *
     * @access  public
     * @return  string              Unique identifier.
     */
    public function getUniqueID()
    {
        return $this->id;
    }

    public function getDescription()
    {
        return $this->rbdigitalRawMetadata->description;
    }

    public function getMoreDetailsOptions()
    {
        global $interface;

        $isbn = $this->getCleanISBN();

        //Load table of contents
        $tableOfContents = $this->getTableOfContents();
        $interface->assign('tableOfContents', $tableOfContents);

        //Load more details options
        $moreDetailsOptions = $this->getBaseMoreDetailsOptions($isbn);

        //Other editions if applicable (only if we aren't the only record!)
        $groupedWorkDriver = $this->getGroupedWorkDriver();
        if ($groupedWorkDriver != null){
            $relatedRecords = $groupedWorkDriver->getRelatedRecords();
            if (count($relatedRecords) > 1) {
                $interface->assign('relatedManifestations', $groupedWorkDriver->getRelatedManifestations());
	            $interface->assign('workId',$groupedWorkDriver->getPermanentId());
                $moreDetailsOptions['otherEditions'] = array(
                    'label' => 'Other Editions and Formats',
                    'body' => $interface->fetch('GroupedWork/relatedManifestations.tpl'),
                    'hideByDefault' => false
                );
            }
        }

        $moreDetailsOptions['moreDetails'] = array(
            'label' => 'More Details',
            'body' => $interface->fetch('Rbdigital/view-more-details.tpl'),
        );
        $this->loadSubjects();
        $moreDetailsOptions['subjects'] = array(
            'label' => 'Subjects',
            'body' => $interface->fetch('RecordDrivers/Rbdigital/view-subjects.tpl'),
        );
        $moreDetailsOptions['citations'] = array(
            'label' => 'Citations',
            'body' => $interface->fetch('Record/cite.tpl'),
        );

        if ($interface->getVariable('showStaffView')) {
            $moreDetailsOptions['staff'] = array(
                'label' => 'Staff View',
                'body' => $interface->fetch($this->getStaffView()),
            );
        }

        return $this->filterAndSortMoreDetailsOptions($moreDetailsOptions);
    }

    public function getItemActions($itemInfo)
    {
        return [];
    }

    public function getISBNs()
    {
        $isbns = [];
        return $isbns;
    }

    public function getISSNs()
    {
        return array();
    }

    public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null)
    {
        $actions = array();
        if ($isAvailable){
//            $actions[] = array(
//                'title' => 'Check Out Rbdigital',
//                'onclick' => "return AspenDiscovery.Rbdigital.checkOutMagazine('{$this->id}');",
//                'requireLogin' => false,
//            );
	        require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
	        $rbdigitalDriver = new RbdigitalDriver();

	        $actions[] = array(
		        'title' => 'Access Online',
		        'url' => $rbdigitalDriver->getUserInterfaceURL() . '/magazine/' . $this->rbdigitalProduct->magazineId . '/' . $this->rbdigitalProduct->issueId,
		        'onclick' => "",
		        'requireLogin' => false,
	        );
        }else{
            $actions[] = array(
                'title' => 'Place Hold Rbdigital',
                'onclick' => "return AspenDiscovery.Rbdigital.placeHoldMagazine('{$this->id}');",
                'requireLogin' => false,
            );
        }
        return $actions;
    }

    /**
     * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
     * @return array
     */
    function getContributors()
    {
        return [];
    }

    /**
     * Get the edition of the current record.
     *
     * @access  protected
     * @return  array
     */
    function getEditions()
    {
        // No specific information provided by Rbdigital
        return array();
    }

    /**
     * @return array
     */
    function getFormats()
    {
        return ['eMagazine'];
    }

    /**
     * Get an array of all the format categories associated with the record.
     *
     * @return  array
     */
    function getFormatCategory()
    {
        return ['eBook'];
    }

    public function getLanguage()
    {
        return $this->rbdigitalProduct->language;
    }

    public function getNumHolds(){
        return 0;
    }

    /**
     * @return array
     */
    function getPlacesOfPublication()
    {
        //Not provided within the metadata
        return array();
    }

    /**
     * Returns the primary author of the work
     * @return String
     */
    function getPrimaryAuthor()
    {
        return "";
    }

    /**
     * @return array
     */
    function getPublishers()
    {
        return [$this->rbdigitalRawMetadata->publisher];
    }

    /**
     * @return array
     */
    function getPublicationDates()
    {
        return [$this->rbdigitalRawMetadata->coverDate];
    }

    protected function getRecordType()
    {
        return 'rbdigital_magazine';
    }

    function getRelatedRecord() {
        $id = 'rbdigital_magazine:' . $this->id;
        return $this->getGroupedWorkDriver()->getRelatedRecord($id);
    }

    public function getSemanticData() {
        // Schema.org
        // Get information about the record
        require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
        $linkedDataRecord = new LDRecordOffer($this->getRelatedRecord());
        $semanticData [] = array(
            '@context' => 'http://schema.org',
            '@type' => $linkedDataRecord->getWorkType(),
            'name' => $this->getTitle(),
            'creator' => $this->getPrimaryAuthor(),
            'bookEdition' => $this->getEditions(),
            'isAccessibleForFree' => true,
            'image' => $this->getBookcoverUrl('medium'),
            "offers" => $linkedDataRecord->getOffers()
        );

        global $interface;
        $interface->assign('og_title', $this->getTitle());
        $interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
        $interface->assign('og_image', $this->getBookcoverUrl('medium'));
        $interface->assign('og_url', $this->getAbsoluteUrl());
        return $semanticData;
    }

    /**
     * Returns title without subtitle
     *
     * @return string
     */
    function getShortTitle()
    {
        return $this->rbdigitalProduct->title;
    }

    /**
     * Returns subtitle
     *
     * @return string
     */
    function getSubtitle()
    {
        return "";
    }

    function isValid(){
        return $this->valid;
    }

    function loadSubjects()
    {
        $subjects = [];
        if ($this->rbdigitalRawMetadata->genre) {
            $subjects[] = $this->rbdigitalRawMetadata->genre;
        }
        global $interface;
        $interface->assign('subjects', $subjects);
    }

    /**
     * @param User $patron
     * @return string mixed
     */
    public function getAccessOnlineLinkUrl($patron)
    {
        global $configArray;
        return $configArray['Site']['url'] . '/RbdigitalMagazine/' . $this->id . '/AccessOnline?patronId=' . $patron->id;
    }

	function getStatusSummary()
	{
		$relatedRecord = $this->getRelatedRecord();
		$statusSummary = array();
		if ($relatedRecord->getAvailableCopies() > 0){
			$statusSummary['status'] = "Available from Rbdigital";
			$statusSummary['available'] = true;
			$statusSummary['class'] = 'available';
			$statusSummary['showCheckout'] = true;
		}else{
			//Rbdigital magazines do not have the ability to place holds
			$statusSummary['status'] = 'Checked Out';
			$statusSummary['class'] = 'checkedOut';
			$statusSummary['available'] = false;
			$statusSummary['showCheckout'] = false;
		}
		return $statusSummary;
	}
}