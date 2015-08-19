<?php

namespace CDSRC\Minify\ViewHelpers;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use TYPO3\Flow\Annotations as Flow;
use CDSRC\Minify\Domain\Model\Resource;

/**
 * Description of LocaleViewHelper
 *
 * @author Matthias Toscanelli <m.toscanelli@code-source.ch>
 */
class MinifyViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
     */
    protected $resourcePublisher;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;
    
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \CDSRC\Minify\Service\Minifier
     */
    protected $minifier;

    /**
     * Minify css and js files
     * 
     * @param array $cssAttributes
     * 
     */
    public function render($cssAttributes = array()) {
        $finalFiles = array('css' => array(), 'js' => array());
        $base = $this->resourcePublisher->getStaticResourcesWebBaseUri();
        $matches = array();
        foreach (preg_split('/(,|\n)/', $this->renderChildren()) as $file) {
            if (preg_match('/^' . preg_quote($base, '/') . 'Packages\/([^\/]+)\/(.*(js|css))$/', trim($file), $matches)) {
                $finalFiles[$matches[3]][] = FLOW_PATH_PACKAGES . 'Application/' . $matches[1] . '/Resources/Public/' . $matches[2];
            }
        }
        $contents = array('css' => '', 'js' => '');
        foreach ($finalFiles as $ext => $files) {
            if (!empty($files)) {
                $datas = $this->minifier->minify($files);
                $resource = $this->resourceManager->createResourceFromContent($datas['content'], $datas['identifier'] . '-minified.' . $ext);
                $this->persistenceManager->whitelistObject($resource);
                $this->persistenceManager->persistAll();
                $uri = $this->resourcePublisher->getPersistentResourceWebUri($resource);
                if ($ext === 'js') {
                    $contents['js'] = '<script type="text/javascript" src="' . $uri . '"></script>';
                } else {
                    $cssAttributes = array_merge($cssAttributes, array(
                        'rel' => 'stylesheet',
                        'type' => 'text/css',
                        'href' => $uri
                    ));
                    $contents['css'] = '<link';
                    foreach ($cssAttributes as $attr => $value) {
                        $contents['css'] .= ' ' . $attr . '="' . htmlspecialchars($value) . '"';
                    }
                    $contents['css'] .= '/>';
                }
            }
        }
        return implode("\n", array_filter($contents));
    }

}
