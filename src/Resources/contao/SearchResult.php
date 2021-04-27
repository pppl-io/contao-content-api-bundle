<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\StringUtil;
use Contao\PageModel;
use Contao\System;
use Contao\File;
use Contao\Search;


class ApiSearchResult extends AugmentedContaoModel
{
    private $allowedQueryTypes = [
        "and",
        "or"
    ];

    private $maxPerPage = 100;
    private $cacheTime = 1800;
    private $contextLength = 48;
    private $totalLength = 1000;

    /**
     * constructor.
     *
     * @param int $id id of the StyleModel
     */
    public function __construct($keywords, $query_type = "and", $root = null, $per_page = 20, $page = 1, $fuzzy = true)
    {
        $strKeywords = trim($keywords);

        $this->per_page = max(1, min(intval($per_page), $this->maxPerPage));
        $this->page = max(1, intval($page));
        $this->fuzzy = !!$fuzzy;
        $this->query_type = in_array($query_type, $this->allowedQueryTypes) ? $query_type : "and";

        $this->keyword = StringUtil::specialchars($strKeywords);
        $this->keywordLabel = $GLOBALS['TL_LANG']['MSC']['keywords'];
        $this->optionsLabel = $GLOBALS['TL_LANG']['MSC']['options'];
        $this->search = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['searchLabel']);
        $this->matchAll = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['matchAll']);
        $this->matchAny = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['matchAny']);

        $this->executeSearch($strKeywords, $root);
    }

    private function getPages($pid) {
        $arrReturn = [];
        $pages = PageModel::findPublishedByPid($pid);
        foreach($pages as $page) {
            $arrReturn[] = $page->id;
            $arrReturn = array_merge($arrReturn, $this->getPages($page->id));
        }
        return $arrReturn;
    }

    private function executeSearch($strKeywords, $root)
    {
        $pages = [];
        $arrResult = null;

        if ($strKeywords === '' || $strKeywords === '*') {
            return;
        }

        if (!$root) {
            $roots = PageModel::findPublishedRootPages(['order' => 'sorting ASC']);
            $root = $roots[0]->id;
        }

        $root = intval($root);
        $this->root = $root;

        $pages = $this->getPages($root);
        //die(json_encode($pages));

        if (empty($pages) || !\is_array($pages)) {
            return [];
        }

        $strCachePath = StringUtil::stripRootDir(System::getContainer()->getParameter('kernel.cache_dir'));

        $strChecksum = md5($strKeywords . $this->query_type . $root . $this->fuzzy);
        $query_starttime = microtime(true);
        $strCacheFile = $strCachePath . '/contao/search/' . $strChecksum . '.json';

        if (file_exists(TL_ROOT . '/' . $strCacheFile)) {
            $objFile = new File($strCacheFile);

            if ($objFile->mtime > time() - $this->cacheTime) {
                $arrResult = json_decode($objFile->getContent(), true);
            } else {
                $objFile->delete();
            }
        }

        if ($arrResult === null) {
            try {
                $objSearch = Search::searchFor($strKeywords, ($this->query_type === 'or'), $pages, 0, 0, $this->fuzzy);
                $arrResult = $objSearch->fetchAllAssoc();
            } catch (\Exception $e) {
                $arrResult = array();
            }

            File::putContent($strCacheFile, json_encode($arrResult));
        }

        $query_endtime = microtime(true);

        //@TODO indexProtected

        $count = count($arrResult);

        $this->count = $count;
        $this->keywords = $strKeywords;

        if ($count < 1 || $this->page > max(ceil($count / $this->per_page), 1)) {
            $this->header = sprintf($GLOBALS['TL_LANG']['MSC']['sEmpty'], $strKeywords);
            $this->duration = substr($query_endtime - $query_starttime, 0, 6) . ' ' . $GLOBALS['TL_LANG']['MSC']['seconds'];
            return;
        }

        $from = (($this->page - 1) * $this->per_page) + 1;
        $to = (($from + $this->per_page) > $count) ? $count : ($from + $this->per_page - 1);

        $results = [];


        for ($i = ($from - 1); $i < $to && $i < $count; $i++) {
            $results[$i]["href"] = $arrResult[$i]['url'];
            $results[$i]["link"] = $arrResult[$i]['title'];
            $results[$i]["url"] = StringUtil::specialchars(urldecode($arrResult[$i]['url']), true, true);
            $results[$i]["title"] = StringUtil::specialchars(StringUtil::stripInsertTags($arrResult[$i]['title']));
            $results[$i]["class"] = (($i == ($from - 1)) ? 'first ' : '') . (($i == ($to - 1) || $i == ($count - 1)) ? 'last ' : '') . (($i % 2 == 0) ? 'even' : 'odd');
            $results[$i]["relevance"] = sprintf($GLOBALS['TL_LANG']['MSC']['relevance'], number_format($arrResult[$i]['relevance'] / $arrResult[0]['relevance'] * 100, 2) . '%');
            $results[$i]["filesize"] = $arrResult[$i]['filesize'];
            $results[$i]["unit"] = $GLOBALS['TL_LANG']['UNITS'][1];
            $results[$i]["matches"] = $arrResult[$i]['matches'];

            $arrContext = array();
            $strText = StringUtil::stripInsertTags($arrResult[$i]['text']);
            $arrMatches = StringUtil::trimsplit(',', $arrResult[$i]['matches']);

            foreach ($arrMatches as $strWord) {
                $arrChunks = array();
                preg_match_all('/(^|\b.{0,' . $this->contextLength . '}\PL)' . str_replace('+', '\\+', $strWord) . '(\PL.{0,' . $this->contextLength . '}\b|$)/ui', $strText, $arrChunks);

                foreach ($arrChunks[0] as $strContext) {
                    $arrContext[] = ' ' . $strContext . ' ';
                }

                // Skip other terms if the total length is already reached
                if (array_sum(array_map('mb_strlen', $arrContext)) >= $this->totalLength) {
                    break;
                }
            }

            if (!empty($arrContext)) {
                $results[$i]["context"] = trim(StringUtil::substrHtml(implode('…', $arrContext), $this->totalLength));
                $results[$i]["context"] = preg_replace('/(?<=^|\PL)(' . implode('|', $arrMatches) . ')(?=\PL|$)/ui', '<mark class="highlight">$1</mark>', $results[$i]["context"]);
                $results[$i]["hasContext"] = true;
            }
        }

        $this->results = $results;
        $this->header = vsprintf($GLOBALS['TL_LANG']['MSC']['sResults'], array($from, $to, $count, $strKeywords));
        $this->duration = substr($query_endtime - $query_starttime, 0, 6) . ' ' . $GLOBALS['TL_LANG']['MSC']['seconds'];
    }
}
