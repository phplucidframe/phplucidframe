<?php
/*
 * This file is part of the PHPLucidFrame library.
 * Core utility for pagination
 *
 *
 * Copyright (c), PHPLucidFrame.
 * @author Sithu K. <cithukyaw@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 * @license	http://www.opensource.org/licenses/mit-license.php MIT License
 */

class Pager{

	private $page 			= 1;		# The current page no.
	private $itemsPerPage 	= 15;		# No. of items per page to display
	private $pageNumLimit 	= 5;		# How many page no. to show in the pagination
	private $imagePath 		= '';		# The absolute image directory path where the navigaion arrow images reside
	private $ajax 			= false;	# AJAX pager or not
	private $url 			= '';		# The URL to request if it is different than the current URL; it must be relative to APP ROOT
	private $total 			= 0;		# Total number of records for the pager
	private $offset 		= 0;		# The calculated offset for the page
	private $enabled 		= true;		# The page is enabled or not
	private $htmlTag		= '<table>';# HTML tag for the pagination display; default is <table>. <ul> and <div> are also allowed.
	private $parentOpenTag;
	private $parentCloseTag;
	private $childTag;
	private $result;					# The array of calculated result pages and offset

	public function Pager($page=1){
		$this->page = $page;
	}
	/**
	 * Setter functions for the properties
	 */
	public function set($key, $value=''){
		if(isset($this->$key)) $this->$key = $value;
		if($key == 'htmlTag') $this->setHtmlTag($value);
	}
	/**
	 * Getter functions for the properties
	 */
	public function get($key){
		if(isset($this->$key)) return $this->$key;
		return '';
	}
	/**
	 * Setter functions for the property "htmlTag"
	 * @param string $value The HTML tag - <table>, <ul> or <div>
	 */
	public function setHtmlTag($value='<table>'){
		if(!in_array($value, array('<table>','<ul>', '<div>'))){
			$this->htmlTag = '<table>';
		}
		switch($this->htmlTag){
			case '<table>':
				$this->parentOpenTag 	= '<table class="pagerTable" border="0" cellpadding="0" cellspacing="0"><tr>';
				$this->parentCloseTag 	= '</tr></table>';
				$this->childTag = 'td';
				break;
			case '<ul>':
				$this->parentOpenTag 	= '<ul class="pagerTable">';
				$this->parentCloseTag 	= '</ul>';
				$this->childTag = 'li';
				break;
			case '<div>':
				$this->parentOpenTag 	= '<div class="pagerTable">';
				$this->parentCloseTag 	= '</div>';
				$this->childTag = 'div';
				break;
		}
	}
	/**
	 * Pager calculation function. Before calling this function, the following property must be set:
	 * 	$page
	 * 	$itemsPerPage
	 * 	$pageNumLimit
	 * 	$total
	 */
	public function calculate(){

		if( ! ($this->page && $this->itemsPerPage && $this->pageNumLimit && $this->total) ){
			$this->enabled = false;
			return false;
		}

		if(!is_numeric($this->page)) $this->page = 1;
		$this->offset = ($this->page - 1) * $this->itemsPerPage;

		$nav = array();
		$nav['offset']   = $this->offset;
		$nav['thisPage'] = $this->page;

		$maxPage = ceil($this->total/$this->itemsPerPage);
		if($this->page <= $this->pageNumLimit){
		  $startPage = 1;
		}else{
		  $startPage = (floor(($this->page-1) / $this->pageNumLimit) * $this->pageNumLimit)+1;
		}

		$j = 0;
		$k = 0;
		$nav['beforePages'] = array();
		$nav['afterPages'] = array();
		for($pageCount=0, $i = $startPage ; $i<=$maxPage; $i++){
			if($i < $this->page){
				$nav['beforePages'][$j] = $i;
				$j++;
			}

			if($i > $this->page){
				$nav['afterPages'][$k] = $i;
				$k++;
			}

			$pageCount ++;
			if($pageCount == $this->pageNumLimit) # display page number only.
			break;
		}

		# First Page
		if ($this->page > 1){
			$nav['firstPageNo'] = 1;
			$nav['firstPageEnable'] = 1;
		}else{
			$nav['firstPageEnable'] = 0;
		}

		# Previous Page
		if ($this->page > 1){
			$nav['prePageNo'] = $this->page-1;
			$nav['prePageEnable'] = 1;
		} else{
			$nav['prePageEnable'] = 0;
		}

		# Next page no.
		if ($this->page < $maxPage)
		{
			$nav['nextPageNo'] = $this->page + 1;
			$nav['nextPageEnable'] = 1;

			$nav['lastPageNo'] = $maxPage;
			$nav['lastPageEnable'] = 1;

		}else{
			$nav['nextPageEnable'] = 0;
			$nav['lastPageEnable'] = 0;
		}
		# Display multi page or not
		if(($maxPage <= 1) || ($this->page > $maxPage)){
			$this->enabled = false;
		}else{
			$this->enabled = true;
		}

		# if page count is less than page num limit, fill page num till page num limit
		if($maxPage > $this->pageNumLimit){
			$allPagesCount = count($nav['beforePages']) + count($nav['afterPages']) + 1;
			if($allPagesCount < $this->pageNumLimit){
				$page = $this->page - 1;
				$filledPageCount = $this->pageNumLimit - $allPagesCount;
				if(isset($nav['beforePages'])) $filledPageCount += count($nav['beforePages']);
				$x = 0;
				while($filledPageCount != $x){
					$filledPages[] = $page;
					$page--;
					$x++;
				}
				$nav['beforePages'] = array();
				sort($filledPages);
				$nav['beforePages'] = $filledPages;
			}
		}

		$this->result = $nav;
		return $this->result;
	}
	/**
	 * Display the pagination
	 */
	public function display(){
		$url 		= ($this->url) ? $this->url : NULL;
		$ajax 		= $this->ajax;
		$imagePath 	= isset($this->imagePath) ? $this->imagePath : '';

		$this->setHtmlTag($this->htmlTag);

		if($this->enabled && $this->result){
			extract($this->result);

			echo $this->parentOpenTag;
			# first
			if($firstPageEnable){
			?>
				<?php echo '<'.$this->childTag.' class="first-enabled">'; ?>
				<?php if($ajax){ ?>
					<a href="<?php echo _url($url); ?>" rel="<?php echo $firstPageNo; ?>">
				<?php }else{ ?>
					<a href="<?php echo _url($url, array('page' => $firstPageNo)); ?>">
				<?php } ?>
					<?php if($imagePath){ ?>
						<img border="0" src="<?php echo $imagePath; ?>start.png" />
					<?php }else{ ?>
						<label><?php echo _t('First'); ?></label>
					<?php } ?>
					</a>
				<?php echo '</'.$this->childTag.'>'; ?>
			<?php
			}else{
			?>
				<?php echo '<'.$this->childTag.' class="first-disabled">'; ?>
				<?php if($imagePath){ ?>
					<img border="0" src="<?php echo $imagePath; ?>start_disabled.png" />
				<?php }else{ ?>
					<label><?php echo _t('First'); ?></label>
				<?php } ?>
				<?php echo '</'.$this->childTag.'>'; ?>
			<?php
			}
			# prev
			if($prePageEnable){
			?>
				<?php echo '<'.$this->childTag.' class="prev-enabled">'; ?>
				<?php if($ajax){ ?>
					<a href="<?php echo _url($url); ?>" rel="<?php echo $prePageNo; ?>">
				<?php }else{ ?>
					<a href="<?php echo _url($url, array('page' => $prePageNo)); ?>">
				<?php } ?>
					<?php if($imagePath){ ?>
						<img border="0" src="<?php echo $imagePath; ?>previous.png" />
					<?php }else{ ?>
						<label><?php echo _t('&laquo; Prev'); ?></label>
					<?php } ?>
					</a>
				<?php echo '</'.$this->childTag.'>'; ?>
			<?php
			}else{
			?>
				<?php echo '<'.$this->childTag.' class="prev-disabled">'; ?>
				<?php if($imagePath){ ?>
					<img border="0" src="<?php echo $imagePath; ?>previous_disabled.png" />
				<?php }else{ ?>
					<label><?php echo _t('&laquo; Prev'); ?></label>
				<?php } ?>
				<?php echo '</'.$this->childTag.'>'; ?>
			<?php
			}
			?>
			<?php echo '<'.$this->childTag.' class="pages">'; ?>
			<?php
				# before pages
				if(isset($beforePages) && $beforePages){
					foreach($beforePages as $oneBeforePage){
				?>
					<span>
					<?php if($ajax){ ?>
						<a href="<?php echo _url($url); ?>" rel="<?php echo $oneBeforePage; ?>"><?php echo $oneBeforePage; ?></a>
					<?php }else{ ?>
						<a href="<?php echo _url($url, array('page' => $oneBeforePage)); ?>"><?php echo $oneBeforePage; ?></a>
					<?php } ?>
					</span>
				<?php
				}
			}
			?>
				<span class="currentPage"><?php echo $thisPage; ?></span>
			<?php
			# after pages
			if(isset($afterPages) && $afterPages){
				foreach($afterPages as $oneAfterPage){
					?>
					<span>
					<?php if($ajax){ ?>
						<a href="<?php echo _url($url); ?>" rel="<?php echo $oneAfterPage; ?>"><?php echo $oneAfterPage; ?></a>
					<?php }else{ ?>
						<a href="<?php echo _url($url, array('page' => $oneAfterPage)); ?>"><?php echo $oneAfterPage; ?></a>
					<?php } ?>
					</span>
					<?
				}
			}
			?>
			<?php echo '</'.$this->childTag.'>'; ?>
			<?php
			# next
			if($nextPageEnable){
				?>
				<?php echo '<'.$this->childTag.' class="next-enabled">'; ?>
				<?php if($ajax){ ?>
					<a href="<?php echo _url($url); ?>" rel="<?php echo $nextPageNo; ?>">
				<?php }else{ ?>
					<a href="<?php echo _url($url, array('page' => $nextPageNo)); ?>">
				<?php } ?>
					<?php if($imagePath){ ?>
						<img border="0" src="<?php echo $imagePath; ?>next.png" />
					<?php }else{ ?>
						<label><?php echo _t('Next &raquo;'); ?></label>
					<?php } ?>
					</a>
				<?php
				echo '</'.$this->childTag.'>';
				?>
			<?php
			}else{
				?>
				<?php
				echo '<'.$this->childTag.' class="next-disabled">';
				?>
				<?php if($imagePath){ ?>
					<img border="0" src="<?php echo $imagePath; ?>next_disabled.png" />
				<?php }else{ ?>
					<label><?php echo _t('Next &raquo;'); ?></label>
				<?php } ?>
				<?php echo '</'.$this->childTag.'>'; ?>
				<?php
			}
			# last
			if($lastPageEnable){
				?>
				<?php
				echo '<'.$this->childTag.' class="last-enabled">';
				?>
				<?php if($ajax){ ?>
					<a href="<?php echo _url($url); ?>" rel="<?php echo $lastPageNo; ?>">
				<?php }else{ ?>
					<a href="<?php echo _url($url, array('page' => $lastPageNo)); ?>">
				<?php } ?>
					<?php if($imagePath){ ?>
						<img border="0" src="<?php echo $imagePath; ?>end.png" />
					<?php }else{ ?>
						<label><?php echo _t('Last'); ?></label>
					<?php } ?>
					</a>
				<?php echo '</'.$this->childTag.'>'; ?>
				<?php
			}else{
				?>
				<?php
				echo '<'.$this->childTag.' class="last-disabled">';
				?>
				<?php if($imagePath){ ?>
					<img border="0" src="<?php echo $imagePath; ?>end_disabled.png" />
				<?php }else{ ?>
					<label><?php echo _t('Last'); ?></label>
				<?php } ?>
				<?php echo '</'.$this->childTag.'>'; ?>
				<?php
			}

			echo $this->parentCloseTag;
		}
	}
}