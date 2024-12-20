<?php

namespace App\Service;

use App\Repository\ChannelRepository;
use App\Repository\RuleRepository;
use App\Repository\LogoRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PlaylistService
{
    public function __construct(
        private ChannelRepository $channelRepository,
        private RuleRepository $ruleRepository,
        private readonly LogoRepository $logoRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ParameterBagInterface $params
    )
    {
        $this->channelRepository = $channelRepository;
        $this->ruleRepository = $ruleRepository;
    }

    public function formatToStaticJSON($channels, $language, $scheme): array
    {
        $channellist = array();
        foreach($channels as $channel){
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $logos = $this->logoRepository->findBy(['channel' => $channel->getId()]);
            $logo_url = null;
            $background = null;
            foreach($logos as $logo){
                if ($logo->getType() == "web") {
                    $logo_url = "https://".$this->params->get('PREFIX_IMAGE_APP_SECURE_URL').$logo->getName();
                    $background = $logo->getBackground();
                    break;
                }
            }

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_APP_URL'));
            if ($scheme == "https") {
                $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_APP_SECURE_URL'));
            }
            $streamUrl = str_replace('{PROVIDE_KEY}', 'PROVIDE_KEY', $streamUrl);
            $streamUrl = $scheme."://".$streamUrl;

            $item = array(
                "category" => $categoryName[$language],
                "channel_name" => $channel->getName(),
                "epg_id" => (string)$channel->getId(),
                "cat_id" => (string)$category->getId(),
                "timeshift" => ($channel->getArchive() > 60) ? 1 : 0,
                "archive" => ($channel->getArchive() > 60) ? 1 : 0,
                "logo_url" => $logo_url,
                "background" => $background,
                "stream_url" => $streamUrl,
            );
            array_push($channellist, $item);
        }
        return $channellist;
    }

    public function formatToJSON($channels, $ottkey, $language): array
    {
        $channellist = array();
        foreach($channels as $channel){
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $arrLogo = array();

            $logos = $this->logoRepository->findBy(['channel' => $channel->getId()]);
            foreach($logos as $key => $logo){
                $arrLogo[$key]['type'] =  $logo->getType();
                $arrLogo[$key]['background'] = $logo->getBackground();
                $arrLogo[$key]['source'] = $this->params->get('PREFIX_IMAGE_URL').$logo->getName();
            }

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($ottkey !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $ottkey, $streamUrl) : $streamUrl;

            $item = [
                "channel_id" => $channel->getId(),
                "channel_name" => $channel->getName(),
                "category_id" => $category->getId(),
                "category_name" => $categoryName[$language],
                "stream_url" => $streamUrl,
                "timeshift" => $channel->getArchive() > 60,
                "archive" => $channel->getArchive(),
                "adult" => $category->getId() === 7,
                "logos" => $arrLogo,
            ];

            array_push($channellist, $item);

        }
        return $channellist;
    }

    public function formatToM3U($channels, $key, $language): string
    {
        $playlist = "#EXTM3U url-tvg=\"".$this->params->get('URL_EPG_XML')."\"\n";

        foreach ($channels as $channel) {
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));
            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $scte35AvailableChannels = explode(',', $this->params->get('SCTE35_AVAILABLE_CHANNELS'));
            if ($key === $this->params->get('TEST_KEY') && in_array($channel->getId(), $scte35AvailableChannels)) {
                $streamUrl = $this->wrapMasterPlaylistURLIntoVidzone($streamUrl, $channel->getId(), $key);
            }

            $params = '';
            //Checking Adult channel for adding "parent-code" parameter
            if ($category->getId() == 7){
                $params .= "parent-code=\"1234\"";
            }
            //Checking Acrhive channel for adding "timeshift" & "tvg-rec" parameters
            if ($channel->getArchive() > 60){
                $params .= "tvg-rec=\"7\" timeshift=\"7\"";
            }
            $playlist .= "#EXTINF:-1 group-title=\"".$categoryName[$language]."\" ".$params.",".$channel->getName()."\n";
            $playlist .= "#EXTGRP:".$categoryName[$language]."\n";
            $playlist .= $streamUrl."\n";
        }

        return $playlist;
    }

    private function wrapMasterPlaylistURLIntoVidzone($streamURL, $channelID, $key): string
    {
        return $this->params->get('VIDZONE_BASE_URL') . "?m=" . base64_encode($streamURL) . "&channel=" . $channelID . "&u=" . $key;
    }

    public function formatToSiptv($channels, $key, $language): string
    {
        $playlist = "#EXTM3U url-tvg=\"".$this->params->get('URL_EPG_XML_SIPTV')."\"\n";

        foreach ($channels as $channel) {
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $params = '';
            //Checking Adult channel for adding "parent-code" parameter
            if ($category->getId() == 7){
                $params .= "parent-code=\"1234\"";
            }
            //Checking Acrhive channel for adding "timeshift" & "tvg-rec" parameters
            if ($channel->getArchive() > 60){
                $params .= "tvg-rec=\"7\" timeshift=\"7\"";
            }
            $playlist .= "#EXTINF:-1 group-title=\"".$categoryName[$language]."\" ".$params.",".$channel->getName()."\n";
            $playlist .= $streamUrl."\n";
        }

        return $playlist;
    }

    public function formatToSSiptv($channels, $key, $language): string
    {
        $playlist = "#EXTM3U catchup=\"shift\" catchup-days=\"7\" x-tvg-url=\"".$this->params->get('URL_EPG_XML_SSIPTV')."\"\n";

        foreach ($channels as $channel) {
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $params = '';
            //Checking Adult channel for adding "parent-code" parameter
            if ($category->getId() == 7){
                $params .= "parent-code=\"1234\"";
            }
            //Checking Acrhive channel for adding "timeshift" & "tvg-rec" parameters
            if ($channel->getArchive() > 60){
                $params .= "tvg-rec=\"7\" timeshift=\"7\"";
            }
            $logoUrl = $this->params->get('PREFIX_IMAGE_URL').$channel->getId().".png";
            $playlist .= "#EXTINF:-1 group-title=\"".$categoryName[$language]."\" "."logo=\"".$logoUrl."\" ".$params.",".$channel->getName()."\n";
            $playlist .= $streamUrl."\n";
        }
        return $playlist;
    }

    public function formatToT2($channels, $key, $language): string
    {
        $playlist = "#EXTM3U \"\n";

        foreach ($channels as $channel) {
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $params = '';
            //Checking Adult channel for adding "parent-code" parameter
            if ($category->getId() == 7){
                $params .= "parent-code=\"1234\"";
            }
            //Checking Acrhive channel for adding "timeshift" & "tvg-rec" parameters
            if ($channel->getArchive() > 60){
                $params .= "tvg-rec=\"7\" timeshift=\"7\"";
            }
            $playlist .= "#EXTINF:-1 group-title=\"".$categoryName[$language]."\" ".$params.",".$this->translit($channel->getName())."\n";
            $playlist .= $streamUrl."\n";
        }
        return $playlist;
    }

    public function formatToSamsung($channels, $key): string
    {
        $playlist = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <items>
        <playlist_name>Standart Playlist</playlist_name>
        <category>
        <category_id>01</category_id>
        <category_title>OTTCLUB</category_title>
        </category>\n";

        foreach ($channels as $channel) {
            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $playlist .= "<channel>\n";
            $playlist .= "<title>".$channel->getName()."</title>\n";
            $playlist .= "<stream_url><![CDATA[".$streamUrl."]]></stream_url>\n";
            $playlist .= "<category_id>01</category_id>\n";
            $playlist .= "</channel>\n";
        }

        $playlist .= "</items>\n";

        return $playlist;
    }

    public function formatToE2Channels($channels, $key): string
    {
        $playlist = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<!-- service references can be found in /etc/enigma2/lamedb -->\n<channels>\n";

        foreach ($channels as $channel) {
            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;
            $streamUrl = urlencode($streamUrl);
            $playlist .= "<channel id=\"".$channel->getId()."\">4097:0:1:".dechex($channel->getId()).":0:0:0:0:0:0:".$streamUrl."</channel> <!-- ".$channel->getName()." -->\n";
        }

        $playlist .= "</channels>\n";

        return $playlist;
    }

    public function formatToE2Bouquet($channels, $key): string
    {
        $playlist = "#NAME OTTCLUB\n";

        foreach ($channels as $channel) {
            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;
            $streamUrl = urlencode($streamUrl);
            $playlist .= "#SERVICE 4097:0:1:".dechex($channel->getId()).":0:0:0:0:0:0:".$streamUrl.":".$channel->getName()."\n";
        }
        return $playlist;
    }

    public function formatToWebtv($channels, $key): string
    {
        $playlist = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <webtvs>\n";

        foreach ($channels as $channel) {
            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));

            $streamUrl = ($key !== '{PROVIDE_KEY}') ? str_replace('{PROVIDE_KEY}', $key, $streamUrl) : $streamUrl;

            $playlist .= "<webtv title=\"".$channel->getName()."\" urlkey=\"0\" url=\"".$streamUrl."\" description=\"".$channel->getName()."\" type=\"1\" group=\"1\" iconsrc=\"\"/>\n";
        }
        $playlist .= "</webtvs>\n";
        return $playlist;
    }

    public function formatToOttplayer($channels, $language): string
    {
        $playlist = "#EXTM3U url-epg=\"".$this->params->get('URL_EPG_OTTPLAYER')."\" url-logo=\"".$this->params->get('PREFIX_IMAGE_URL')."\"\n";

        foreach ($channels as $channel) {
            $category = $this->categoryRepository->findOneBy(['id' => $channel->getCategory()->getId()]);
            $categoryName = $category->getName();

            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_URL'));
            $streamUrl = str_replace('{PROVIDE_KEY}', '{KEY}', $streamUrl);

            $params = "tvg-rec=\"0\"";
            //Checking Archive channel for adding ""tvg-rec" parameter
            if ($channel->getArchive() > 60){
                $params = "tvg-rec=\"1\"";
            }
            //Checking Adult channel for adding "adult" parameter
            if ($category->getId() == 7){
                $params .= " adult=\"1\"";
            }


            $playlist .= "#EXTINF:-1 tvg-id=\"".$channel->getId()."\" tvg-logo=\"".$channel->getId().".png\" group-title=\"".$categoryName[$language]."\" ".$params." ,".$channel->getName()."\n";
            $playlist .= $streamUrl."\n";
        }
        return $playlist;
    }

    public function formatToDune($channels, $categories, $language): string
    {
        $channelLogo = array(
            "1" => "plugin_file://icons/sport.png",
            "2" => "plugin_file://icons/pozn.png",
            "3" => "plugin_file://icons/kino.png",
            "4" => "plugin_file://icons/detskie.png",
            "5" => "plugin_file://icons/other.png",
            "6" => "plugin_file://icons/muz.png",
            "7" => "plugin_file://icons/xxx.png",
            "8" => "plugin_file://icons/zar.png",
            "9" => "plugin_file://icons/nov.png",
        );

        $playlist = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $playlist .= "<tv_info>\n";
        $playlist .= "<tv_categories>\n";

        foreach ($categories as $category) {

            $logo = $channelLogo[$category->getId()];
            $categoryName = $category->getName()[$language];
            $categoryId = $category->getId();

            $playlist .= "<tv_category>\n";
            $playlist .= "<id>$categoryId</id>\n";
            $playlist .= "<caption>$categoryName</caption>\n";
            $playlist .= "<icon_url>$logo</icon_url>\n";
            $playlist .= "</tv_category>\n";
        }
        $playlist .= "</tv_categories>\n";
        $playlist .= "<tv_channels>\n";

        foreach ($channels as $channel) {
            $streamUrl = str_replace('{CHANNEL_ID}', $channel->getId(), $this->params->get('TEMPLATE_STREAM_DUNE_URL'));
            $playlist .= "<tv_channel>\n";
            $playlist .= "<caption>{$channel->getName()}</caption>";
            $playlist .= "<epg_id>{$channel->getId()}</epg_id>\n";
            if ($channel->getCategory()->getId() == 7){
                $playlist .= "<protected>1</protected>\n";
            }
            $playlist .= "<icon_url>{$this->params->get('PREFIX_IMAGE_URL')}{$channel->getId()}.png</icon_url>\n";
            $playlist .= "<num_past_epg_days>7</num_past_epg_days>\n";
            $playlist .= "<num_future_epg_days>2</num_future_epg_days>\n";
            $playlist .= "<tv_categories>\n";
            $playlist .= "<tv_category_id>{$channel->getCategory()->getId()}</tv_category_id>\n";
            $playlist .= "</tv_categories>\n";
            $playlist .= "<streaming_url>$streamUrl</streaming_url>\n";
            if ($channel->getArchive() > 60){
                $playlist .= "<num_timeshift_hours>1</num_timeshift_hours>\n";
                $playlist .= "<archive>1</archive>\n";
            } else {
                $playlist .= "<num_timeshift_hours>0</num_timeshift_hours>\n";
                $playlist .= "<archive>0</archive>\n";
            }
            $playlist .= "</tv_channel>\n";
        }

        $playlist .= "</tv_channels>\n";
        $playlist .= "</tv_info>\n";

        return $playlist;
    }

    private function translit($str) {
        $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        return str_replace($rus, $lat, $str);
    }

}