<?php

namespace Imdb;

class TitleSearch extends MdbBase
{

    const MOVIE          = Title::MOVIE;
    const TV_SERIES      = Title::TV_SERIES;
    const TV_EPISODE     = Title::TV_EPISODE;
    const TV_MINI_SERIES = Title::TV_MINI_SERIES;
    const TV_MOVIE       = Title::TV_MOVIE;
    const TV_SPECIAL     = Title::TV_SPECIAL;
    const TV_SHORT       = Title::TV_SHORT;
    const GAME           = Title::GAME;
    const VIDEO          = Title::VIDEO;
    const SHORT          = Title::SHORT;

    protected function buildUrl($searchTerms = null)
    {
        return "http://" . $this->imdbsite . "/find?s=tt&q=" . urlencode($searchTerms);
    }

    protected function parseTitleType($string)
    {
        $string = strtoupper($string);

        if (strpos($string, 'TV SERIES') !== false) {
            return self::TV_SERIES;
        }

        if (strpos($string, 'TV EPISODE') !== false) {
            return self::TV_EPISODE;
        }

        if (strpos($string, 'VIDEO GAME') !== false) {
            return self::GAME;
        }

        if (strpos($string, '(VIDEO)') !== false) {
            return self::VIDEO;
        }

        if (strpos($string, '(SHORT)') !== false) {
            return self::SHORT;
        }

        if (strpos($string, 'TV MINI-SERIES)') !== false) {
            return self::TV_MINI_SERIES;
        }

        if (strpos($string, 'TV MOVIE)') !== false) {
            return self::TV_MOVIE;
        }

        if (strpos($string, 'TV SPECIAL)') !== false) {
            return self::TV_SPECIAL;
        }

        if (strpos($string, 'TV SHORT)') !== false) {
            return self::TV_SHORT;
        }

        return self::MOVIE;
    }

    /**
     * Search IMDb for titles matching $searchTerms
     *
     * @param string $searchTerms
     * @param array  $wantedTypes *optional* imdb types that should be returned. Defaults to returning all types.
     *                            The class constants MOVIE,GAME etc should be used e.g. [TitleSearch::MOVIE, TitleSearch::TV_SERIES]
     *
     * @param null   $maxResults
     *
     * @return Title[] array of Title objects
     * @throws \Imdb\Exception\Http
     */
    public function search($searchTerms, $wantedTypes = null, $maxResults = null)
    {
        $results = [];

        $page = $this->getPage($searchTerms);

        // Parse & filter results
        if (preg_match_all('!class="result_text"\s*>\s*<a href="/title/tt(?<imdbid>\d{7})/[^>]*>(?<title>.*?)</a>\s*(\([^\d]+\)\s*)?(\((?<year>\d{4})(.*?|)\)|)(?<type>[^<]*)!ims',
            $page, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $this->parseTitleType($match['type']);

                if (is_array($wantedTypes) && !in_array($type, $wantedTypes)) {
                    continue;
                }

                $results[] = Title::fromSearchResult($match['imdbid'], $match['title'], $match['year'], $type, $this->config, $this->logger,
                    $this->cache);
            }
        }

        return $results;
    }
}
