<?php

declare(strict_types=1);

namespace Cri\CriPeertube\Helper;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Peertube helper class
 */
class PeertubeHelper extends AbstractOEmbedHelper
{

    
    public $server = "";
    public $url = "";
    public $ombedurl = "";

    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        // URL zerlegen
        $parts = parse_url($this->url);
        
        
        if ($parts === false || !isset($parts['path'])) {
            return null;
        }
        
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cri_peertube');
        
        $domain = $extConf['peertubeServer'];       
   
        // Host in Punycode umwandeln
        $punycodeHost = rawurlencode(idn_to_ascii(
            $domain,
            IDNA_DEFAULT,
            INTL_IDNA_VARIANT_UTS46
            ));
        
        $oembedurl = $domain."services/oembed?url=".$punycodeHost."videos%2Fwatch%2F".$mediaId;
   
        return $oembedurl;
         
    }


    
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        // URL zerlegen
        $parts = parse_url($url);
        
          if ($parts === false || !isset($parts['path'])) {
           return null;
        }
        
        $this->url = $url;
        $domain = $parts['scheme'] . '://' . $parts['host']."/" ;
        // Host in Punycode umwandeln

        $this->server = $domain;
        
        // Pfad zerlegen
        $segments = explode('/', trim($parts['path'], '/'));
        
        // Erwartet: /w/<id>
        if (count($segments) === 2 && $segments[0] === 'w') {
            $videoId = $segments[1];
        } else {
            throw new RuntimeException('Keine gültige PeerTube-URL');
        }        
  
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cri_peertube');
        
        if ($domain != $extConf['peertubeServer'])
        {
            return null;
        }

        return $this->transformMediaIdToFile($videoId , $targetFolder, $this->extension);
    }

    /**
     * Transform mediaId to File
     *
     * We override the abstract function so that we can integrate our own handling for the title field
     *
     * @param string $mediaId
     * @param Folder $targetFolder
     * @param string $fileExtension
     * @return File
     */
    protected function transformMediaIdToFile($mediaId, Folder $targetFolder, $fileExtension)
    {
        $file = $this->findExistingFileByOnlineMediaId($mediaId, $targetFolder, $fileExtension);
        if ($file === null) {
            $fileName = $mediaId . '.' . $fileExtension;

            $oEmbed = $this->getOEmbedData($mediaId);
            if (!empty($oEmbed['title'])) {
                $title = $this->handlePeertubeTitle($oEmbed['title']);
                if (!empty($title)) {
                    $fileName = $title . '.' . $fileExtension;
                }
            }
            $file = $this->createNewFile($targetFolder, $fileName, $mediaId);
        }
        return $file;
    }

    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
        $videoId = $this->getOnlineMediaId($file);
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cri_peertube');
        
        return $extConf['peertubeServer']."/w/".$videoId;
             
        
    }

    public function getPreviewImage(File $file)
    {
        $properties = $file->getProperties();
        $previewImageUrl = $properties['peertube_thumbnail_url'] ?? '';

        $datawrapperId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'peertube_' . md5($datawrapperId) . '.jpg';

        if (!empty($previewImageUrl)) {
            $previewImage = GeneralUtility::getUrl($previewImageUrl);
            file_put_contents($temporaryFileName, $previewImage);
            GeneralUtility::fixPermissions($temporaryFileName);
            return $temporaryFileName;
        }

        return '';
    }

    /**
     * Get meta data for OnlineMedia item
     * Using the meta data from oEmbed
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file)
    {
        $metaData = [];
        $mediaid= $this->getOnlineMediaId($file);
     
        $url = $this->getOEmbedUrl($mediaid, 'json');
       
        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        $oEmbed = (array)json_decode($result);
     
       
        if ($oEmbed) {

             $metaData['width'] = $oEmbed['width'];
       
             $metaData['height'] = $oEmbed['height'];
            if (empty($file->getProperty('title'))) {
                $metaData['title'] = $this->handlePeertubeTitle($oEmbed['title']);
            }
            $metaData['peertube_html'] = $oEmbed['html'];
            $metaData['peertube_thumbnail_url'] = $oEmbed['thumbnail_url'];
          
        }

        return $metaData;
    }

    /**
     * @param string $title
     * @return string
     */
    protected function handlePeertubeTitle(string $title): string
    {
        return trim(mb_substr(strip_tags($title), 0, 255));
    }
}
