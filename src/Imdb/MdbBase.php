<?php
#############################################################################
# PHP MovieAPI                                          (c) Itzchak Rehberg #
# written by Itzchak Rehberg <izzysoft AT qumran DOT org>                   #
# http://www.izzysoft.de/                                                   #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

use mrcnpdlk\Psr16Cache\Adapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * Accessing Movie information
 *
 * @author        Georgos Giagas
 * @author        Izzy (izzysoft AT qumran DOT org)
 * @copyright (c) 2002-2004 by Giorgos Giagas and (c) 2004-2009 by Itzchak Rehberg and IzzySoft
 */
class MdbBase extends Config
{
    public $version = '5.0.3';

    protected $months = [
        'January'   => '01',
        'February'  => '02',
        'March'     => '03',
        'April'     => '04',
        'May'       => '05',
        'June'      => '06',
        'July'      => '07',
        'August'    => '08',
        'September' => '09',
        'October'   => '10',
        'November'  => '11',
        'December'  => '12',
    ];

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Pages
     */
    protected $pages;

    /**
     * @var array
     */
    protected $page = [];

    /**
     * @var string 7 digit identifier for this person
     */
    protected $imdbID;

    /**
     * @var \mrcnpdlk\Psr16Cache\Adapter
     */
    protected $oAdapter;

    /**
     * @param Config          $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger
     * @param CacheInterface  $cache  OPTIONAL override default cache
     *
     */
    public function __construct(Config $config = null, LoggerInterface $logger = null, CacheInterface $cache = null)
    {
        parent::__construct();

        if ($config) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }

        $this->config   = $config ?: $this;
        $this->logger   = $logger ?: new NullLogger();
        $this->cache    = $cache;
        $this->oAdapter = new Adapter($this->cache, $this->logger);

        $this->pages = new Pages($this->config, $this->cache, $this->logger);
    }

    /**
     * Overrideable method to build the URL used by getPage
     *
     * @param string $context OPTIONAL
     *
     * @return string
     */
    protected function buildUrl($context = null)
    {
        return '';
    }

    protected function debug_html($html)
    {
        $this->logger->error(htmlentities($html));
    }

    #---------------------------------------------------------[ Debug helpers ]---

    protected function debug_object($object)
    {
        $this->logger->error('{object}', ['object' => $object]);
    }

    protected function debug_scalar($scalar)
    {
        $this->logger->error($scalar);
    }

    /**
     * Get a page from IMDb, which will be cached in memory for repeated use
     *
     * @param string $context Name of the page or some other context to build the URL with to retrieve the page
     *
     * @return string
     * @throws \Imdb\Exception\Http
     */
    protected function getPage($context = null)
    {
        return $this->pages->get($this->buildUrl($context));
    }

    /**
     * Retrieve the IMDB ID
     *
     * @return string id IMDBID currently used
     */
    public function imdbid()
    {
        return $this->imdbID;
    }

    /**
     * Get numerical value for month name
     *
     * @param string name name of month
     *
     * @return integer month number
     */
    protected function monthNo($mon)
    {
        return @$this->months[$mon];
    }

    /**
     * Set and validate the IMDb ID
     *
     * @param string id IMDb ID
     */
    protected function setid($id)
    {
        if (is_numeric($id)) {
            $this->imdbID = str_pad($id, 7, '0', STR_PAD_LEFT);
        } elseif (preg_match("/(?:nm|tt)(\d{7})/", $id, $matches)) {
            $this->imdbID = $matches[1];
        } else {
            $this->debug_scalar("<BR>setid: Invalid IMDB ID '$id'!<BR>");
        }
    }

}
