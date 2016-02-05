<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for pagination
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for pagination
 */
class Pager
{
    /** @var int The current page no. */
    private $page = 1;
    /** @var int The customized query string name for "page" */
    private $pageQueryStr = 'page';
    /** @var int No. of items per page to display */
    private $itemsPerPage = 15;
    /** @var int How many page no. to show in the pagination */
    private $pageNumLimit = 5;
    /** @var string The absolute image directory path where the navigaion arrow images reside */
    private $imagePath = '';
    /** @var boolean AJAX pager or not */
    private $ajax = false;
    /** @var string The URL to request if it is different than the current URL; it must be relative to APP ROOT */
    private $url = '';
    /** @var int Total number of records for the pager */
    private $total = 0;
    /** @var int The calculated offset for the page */
    private $offset = 0;
    /** @var boolean The page is enabled or not */
    private $enabled = true;
    /** @var string HTML tag for the pagination display; default is <table>. <ul> and <div> are also allowed. */
    private $htmlTag = '<table>';
    /** @var string HTML tag for internal use */
    private $parentOpenTag;
    /** @var string HTML tag for internal use */
    private $parentCloseTag;
    /** @var string HTML tag for internal use */
    private $childTag;
    /** @var array The array of calculated result pages and offset */
    private $result;

    /**
     * Constructor
     * @param string $pageQueryStr The customized page query string name
     */
    public function __construct($pageQueryStr = '')
    {
        if ($pageQueryStr) {
            $this->pageQueryStr = $pageQueryStr;
        }
        $page = _arg($this->pageQueryStr);
        $this->page = ( $page ) ? $page : 1;
    }

    /**
     * Setter functions for the properties
     * @param string $key The property name
     * @param mixed $value The value to be set to the property
     * @return object Pager
     */
    public function set($key, $value = '')
    {
        if (isset($this->$key)) {
            $this->$key = $value;
        }
        if ($key == 'htmlTag') {
            $this->setHtmlTag($value);
        }

        return $this;
    }

    /**
     * Getter functions for the properties
     * @param string $key The property name
     * @return mixed The value of the property
     */
    public function get($key)
    {
        return (isset($this->$key)) ? $this->$key : '';
    }

    /**
     * @internal
     * Setter functions for the property "htmlTag"
     * @param string $value The HTML tag - <table>, <ul> or <div>
     * @return object Pager
     */
    private function setHtmlTag($tag = '<table>')
    {
        if (!in_array($tag, array('<table>','<ul>', '<div>'))) {
            $this->htmlTag = '<table>';
        }
        switch($this->htmlTag) {
            case '<table>':
                $this->parentOpenTag = '<table class="pager" border="0" cellpadding="0" cellspacing="0"><tr>';
                $this->parentCloseTag = '</tr></table>';
                $this->childTag = 'td';
                break;
            case '<ul>':
                $this->parentOpenTag = '<ul class="pager">';
                $this->parentCloseTag = '</ul>';
                $this->childTag = 'li';
                break;
            case '<div>':
                $this->parentOpenTag = '<div class="pager">';
                $this->parentCloseTag = '</div>';
                $this->childTag = 'div';
                break;
        }

        return $this;
    }

    /**
     * Pager calculation function
     * Before calling this function, the following property must be set:
     *
     * - $page
     * - $itemsPerPage
     * - $pageNumLimit
     * - $total
     *
     * The array of the offsets
     *  Array(
     *      [offset] => xx
     *      [thisPage] => xx
     *      [beforePages] => Array()
     *      [afterPages] => Array()
     *      [firstPageEnable] => xx
     *      [prePageEnable] => xx
     *      [nextPageNo] => xx
     *      [nextPageEnable] => xx
     *      [lastPageNo] => xx
     *      [lastPageEnable] => xx
     *  )
     *
     * @return object Pager
     */
    public function calculate()
    {

        if (!($this->page && $this->itemsPerPage && $this->pageNumLimit && $this->total)) {
            $this->enabled = false;
            return false;
        }

        if (!is_numeric($this->page)) {
            $this->page = 1;
        }
        $this->offset = ($this->page - 1) * $this->itemsPerPage;

        $nav = array();
        $nav['offset']   = $this->offset;
        $nav['thisPage'] = $this->page;

        $maxPage = ceil($this->total/$this->itemsPerPage);
        if ($this->page <= $this->pageNumLimit) {
            $startPage = 1;
        } else {
            $startPage = (floor(($this->page-1) / $this->pageNumLimit) * $this->pageNumLimit)+1;
        }

        $j = 0;
        $k = 0;
        $nav['beforePages'] = array();
        $nav['afterPages'] = array();
        for ($pageCount=0, $i = $startPage; $i<=$maxPage; $i++) {
            if ($i < $this->page) {
                $nav['beforePages'][$j] = $i;
                $j++;
            }

            if ($i > $this->page) {
                $nav['afterPages'][$k] = $i;
                $k++;
            }

            $pageCount ++;
            if ($pageCount == $this->pageNumLimit) {
                # display page number only.
                break;
            }
        }

        # First Page
        if ($this->page > 1) {
            $nav['firstPageNo'] = 1;
            $nav['firstPageEnable'] = 1;
        } else {
            $nav['firstPageEnable'] = 0;
        }

        # Previous Page
        if ($this->page > 1) {
            $nav['prePageNo'] = $this->page-1;
            $nav['prePageEnable'] = 1;
        } else {
            $nav['prePageEnable'] = 0;
        }

        # Next page no.
        if ($this->page < $maxPage) {
            $nav['nextPageNo'] = $this->page + 1;
            $nav['nextPageEnable'] = 1;

            $nav['lastPageNo'] = $maxPage;
            $nav['lastPageEnable'] = 1;

        } else {
            $nav['nextPageEnable'] = 0;
            $nav['lastPageEnable'] = 0;
        }
        # Display multi page or not
        if (($maxPage <= 1) || ($this->page > $maxPage)) {
            $this->enabled = false;
        } else {
            $this->enabled = true;
        }

        # if page count is less than page num limit, fill page num till page num limit
        if ($maxPage > $this->pageNumLimit) {
            $allPagesCount = count($nav['beforePages']) + count($nav['afterPages']) + 1;
            if ($allPagesCount < $this->pageNumLimit) {
                $page = $this->page - 1;
                $filledPageCount = $this->pageNumLimit - $allPagesCount;
                if (isset($nav['beforePages'])) {
                    $filledPageCount += count($nav['beforePages']);
                }
                $x = 0;
                while ($filledPageCount != $x) {
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
        return $this;
    }

    /**
     * Display the pagination
     * @return void
     */
    public function display()
    {
        $url        = ($this->url) ? $this->url : null;
        $ajax       = $this->ajax;
        $imagePath  = isset($this->imagePath) ? $this->imagePath : '';

        $this->setHtmlTag($this->htmlTag);

        if ($this->enabled && $this->result) {
            extract($this->result);

            echo $this->parentOpenTag;
            # first
            if ($firstPageEnable) {
                echo '<'.$this->childTag.' class="first-enabled">';
                if ($ajax) {
                    echo '<a href="'._url($url).'" rel="'.$firstPageNo.'">';
                } else {
                    echo '<a href="'._url($url, array($this->pageQueryStr => $firstPageNo)).'">';
                }
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'start.png" />';
                } else {
                    echo '<label>'._t('First').'</label>';
                }
                echo '</a>';
                echo '</'.$this->childTag.'>';
            } else {
                echo '<'.$this->childTag.' class="first-disabled">';
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'start_disabled.png" />';
                } else {
                    echo '<label>'._t('First').'</label>';
                }
                echo '</'.$this->childTag.'>';
            }
            # prev
            if ($prePageEnable) {
                echo '<'.$this->childTag.' class="prev-enabled">';
                if ($ajax) {
                    echo '<a href="'._url($url).'" rel="'.$prePageNo.'">';
                } else {
                    echo '<a href="'._url($url, array($this->pageQueryStr => $prePageNo)).'">';
                }
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'previous.png" />';
                } else {
                    echo '<label>'._t('&laquo; Prev').'</label>';
                }
                echo '</a>';
                echo '</'.$this->childTag.'>';
            } else {
                echo '<'.$this->childTag.' class="prev-disabled">';
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'previous_disabled.png" />';
                } else {
                    echo '<label>'._t('&laquo; Prev').'</label>';
                }
                echo '</'.$this->childTag.'>';
            }
            echo '<'.$this->childTag.' class="pages">';

            # before pages
            if (isset($beforePages) && $beforePages) {
                foreach ($beforePages as $oneBeforePage) {
                    echo '<span>';
                    if ($ajax) {
                        echo '<a href="'._url($url).'" rel="'.$oneBeforePage.'">'.$oneBeforePage.'</a>';
                    } else {
                        echo '<a href="'._url($url, array($this->pageQueryStr => $oneBeforePage)).'">';
                        echo $oneBeforePage;
                        echo '</a>';
                    }
                    echo '</span>';
                }
            }
            echo '<span class="currentPage">'.$thisPage.'</span>';

            # after pages
            if (isset($afterPages) && $afterPages) {
                foreach ($afterPages as $oneAfterPage) {
                    echo '<span>';
                    if ($ajax) {
                        echo '<a href="'._url($url).'" rel="'.$oneAfterPage.'">'.$oneAfterPage.'</a>';
                    } else {
                        echo '<a href="'._url($url, array($this->pageQueryStr => $oneAfterPage)).'">';
                        echo $oneAfterPage;
                        echo '</a>';
                    }
                    echo '</span>';
                }
            }
            echo '</'.$this->childTag.'>';

            # next
            if ($nextPageEnable) {
                echo '<'.$this->childTag.' class="next-enabled">';
                if ($ajax) {
                    echo '<a href="'._url($url).'" rel="'.$nextPageNo.'">';
                } else {
                    echo '<a href="'._url($url, array($this->pageQueryStr => $nextPageNo)).'">';
                }
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'next.png" />';
                } else {
                    echo '<label>'._t('Next &raquo;').'</label>';
                }
                echo '</a>';
                echo '</'.$this->childTag.'>';
            } else {
                echo '<'.$this->childTag.' class="next-disabled">';
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'next_disabled.png" />';
                } else {
                    echo '<label>'._t('Next &raquo;').'</label>';
                }
                echo '</'.$this->childTag.'>';
            }

            # last
            if ($lastPageEnable) {
                echo '<'.$this->childTag.' class="last-enabled">';
                if ($ajax) {
                    echo '<a href="'._url($url).'" rel="'.$lastPageNo.'">';
                } else {
                    echo '<a href="'._url($url, array($this->pageQueryStr => $lastPageNo)).'">';
                }
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'end.png" />';
                } else {
                    echo '<label>'._t('Last').'</label>';
                }
                echo '</a>';
                echo '</'.$this->childTag.'>';
            } else {
                echo '<'.$this->childTag.' class="last-disabled">';
                if ($imagePath) {
                    echo '<img border="0" src="'.$imagePath.'end_disabled.png" />';
                } else {
                    echo '<label>'._t('Last').'</label>';
                }
                echo '</'.$this->childTag.'>';
            }

            echo $this->parentCloseTag;
        }
    }
}
