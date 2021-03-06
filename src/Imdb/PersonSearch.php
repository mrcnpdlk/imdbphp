<?php

namespace Imdb;

/**
 * Search for people on IMDb
 *
 * @author    Izzy (izzysoft AT qumran DOT org)
 * @copyright 2008-2009 by Itzchak Rehberg and IzzySoft
 */

class PersonSearch extends MdbBase
{

    public $name;
    public $resu = [];

    /**
     * Create the IMDB URL for the name search
     *
     * @param null $context
     *
     * @return string url
     */
    protected function buildUrl($context = null)
    {
        return 'http://' . $this->imdbsite . '/find?q=' . urlencode($this->name) . '&s=nm';
    }

    /**
     * Reset search results
     * This empties the collected search results. Without calling this, every
     * new search appends its results to the ones collected by the previous search.
     */
    public function reset()
    {
        $this->resu = [];
    }

    /**
     * Setup search results
     *
     * @return Person[]
     * @throws \Imdb\Exception\Http
     */
    public function results()
    {
        $page = $this->getPage();

        // make sure to catch col #3, not #1 (pic only)
        //                        photo           name                   1=id        2=name        3=details
        preg_match_all('|<tr.*>\s*<td.*>.*</td>\s*<td.*<a href="/name/nm(\d{7})[^>]*>([^<]+)</a>\s*(.*)</td>|Uims', $page, $matches);
        $mc = count($matches[0]);
        $this->logger->debug("[Person Search] $mc matches");
        $mids_checked = [];
        for ($i = 0; $i < $mc; ++$i) {
            $pid = $matches[1][$i];
            if (in_array($pid, $mids_checked)) {
                {
                    continue;
                }
            }
            $mids_checked[] = $pid;
            $name           = $matches[2][$i];
            $info           = $matches[3][$i];
            $resultPerson   = Person::fromSearchResults($pid, $name, $this->config, $this->logger, $this->cache);
            if (!empty($info) && preg_match('|<small>\((.*),\s*<a href="/title/tt(\d{7}).*"\s*>(.*)</a>\s*\((\d{4})\)\)|Ui', $info,
                    $match)) {
                        $role  = $match[1];
                        $mid   = $match[2];
                        $movie = $match[3];
                        $year  = $match[4];
                        $resultPerson->setSearchDetails($role, $mid, $movie, $year);
                    }
            $this->resu[$i] = $resultPerson;
            unset($resultPerson);
        }

        return $this->resu;
    }

    /**
     * Search for people on imdb who match $searchTerms
     * @method search
     *
     * @param string $searchTerms
     *
     * @return Person[]
     * @throws \Imdb\Exception\Http
     */
    public function search($searchTerms)
    {
        $this->setsearchname($searchTerms);
        $this->reset();

        return $this->results();
    }

    /**
     * Set the name (title) to search for
     * @method setsearchname
     *
     * @param string searchstring what to search for - (part of) the movie name
     */
    public function setsearchname($name)
    {
        $this->name = $name;
    }

}
