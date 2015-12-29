<?php

namespace CDSRC\Minify\Service;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use TYPO3\Flow\Annotations as Flow;

require_once(FLOW_PATH_PACKAGES . 'Libraries/mrclay/minify/min/lib/Minify.php');
require_once(FLOW_PATH_PACKAGES . 'Libraries/mrclay/minify/min/lib/Minify/Loader.php');

/**
 * Description of Minifier
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 * @Flow\Scope("singleton")
 */
class Minifier {

    const CACHE_IDENTIFIER = 'Cdsrc_Minify_Content_Storage';

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Cache\CacheManager 
     */
    protected $cacheManager;

    /**
     * Constructor
     * 
     */
    public function __construct() {
        \Minify_Loader::register();
    }

    /**
     * Minify files
     * 
     * @param array $files
     */
    public function minify(array $files) {
        $mtime = 0;
        $cache = $this->cacheManager->getCache(self::CACHE_IDENTIFIER);
        foreach ($files as $key => $file) {
            if (is_string($file) && strlen($file) > 0 && is_file($file)) {
                $mtime = max(array(filemtime($file), $mtime));
            } else {
                unset($files[$key]);
            }
        }
        $entryIdentifier = sha1(implode('|', array_values($files)));
        $datas = NULL;
        if ($cache->isValidEntryIdentifier($entryIdentifier)) {
            $datas = $cache->get($entryIdentifier);
        }
        if ($datas === NULL || $datas['mtime'] < $mtime) {
            $datas = array(
                'identifier' => $entryIdentifier,
                'mtime' => $mtime,
                'content' => \Minify::combine($files)
            );
            if (!empty($datas['content'])) {
                $cache->set($entryIdentifier, $datas);
            }
        }
        return $datas;
    }
    
    /**
     * Get persistent URI hash
     * 
     * @return string
     */
    public function getPublicPersistentHash(){
        return substr(sha1(self::CACHE_IDENTIFIER), 0, 10);
    }

}
