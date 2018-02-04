<?php 

class Autocompleteplus_Autosuggest_Block_Autocorrection extends Mage_Core_Block_Template {

	protected function _prepareLayout() {
		
		$header = $this->__("Search results for '%s'", $this->helper('catalogsearch')->getEscapedQueryText());
		if( Mage::registry('search_results_for') ) {						
			$header = $this->__("No results for '%s', showing results for '%s'",  $this->helper('catalogsearch')->getQueryText(), Mage::registry('search_results_for') );
		}	
		$alternatives = '';
		if( Mage::registry('search_alternatives') ) {
			$links = array();
			foreach(Mage::registry('search_alternatives') as $alternative) {
				$links[] = '<a href="' . $this->getUrl('catalogsearch/result', array('q' => $alternative)) . '">' . $alternative . '</a>';
			}

			$alternatives = '</h1><strong>' . $this->__('Did you mean:') . '</strong><p>' . implode(', ', $links) . '</p>';
		}

		if ( Mage::registry('search_results_for') || Mage::registry('search_alternatives')) {

			$this->getLayout()
				 ->getBlock('search.result')
				 ->setHeaderText($header . $alternatives);
		}
	}
}