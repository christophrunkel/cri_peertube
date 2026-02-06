<?php

use Cri\CriPeertube\Helper\PeertubeHelper;
use Cri\CriPeertube\Rendering\PeertubeRenderer;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') || die();

(function ($mediaFileExt) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers'][$mediaFileExt] = PeertubeHelper::class;

    $rendererRegistry = GeneralUtility::makeInstance(RendererRegistry::class);
    $rendererRegistry->registerRendererClass(PeertubeRenderer::class);

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'][$mediaFileExt] = 'video/' . $mediaFileExt;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',' . $mediaFileExt;

    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerFileExtension($mediaFileExt, 'mimetypes-media-video-' . $mediaFileExt);

})('peertube');
