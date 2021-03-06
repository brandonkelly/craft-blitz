<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\drivers\storage;

use Craft;
use putyourlightson\blitz\models\SiteUriModel;
use yii\caching\CacheInterface;
use yii\db\Exception;

/**
 *
 * @property mixed $settingsHtml
 */
class YiiCacheStorage extends BaseCacheStorage
{
    // Constants
    // =========================================================================

    /**
     * @const string
     */
    const KEY_PREFIX = 'blitz';

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $cacheComponent = 'cache';

    /**
     * @var CacheInterface|null
     */
    private $_cache;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('blitz', 'Yii Cache Storage');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->_cache = Craft::$app->get($this->cacheComponent, false);
    }

    /**
     * @inheritdoc
     */
    public function get(SiteUriModel $siteUri): string
    {
        if ($this->_cache === null) {
            return '';
        }

        $value = '';

        // Redis cache throws an exception if the connection is broken, so we catch it here
        try {
            // Cast the site ID to an integer to avoid an incorrect key
            // https://github.com/putyourlightson/craft-blitz/issues/257
            $value = $this->_cache->get([
                self::KEY_PREFIX, (int)$siteUri->siteId, $siteUri->uri
            ]);
        }
        catch (Exception $e) {}

        return $value ?: '';
    }

    /**
     * @inheritdoc
     */
    public function save(string $value, SiteUriModel $siteUri)
    {
        if ($this->_cache === null) {
            return;
        }

        // Cast the site ID to an integer to avoid an incorrect key
        // https://github.com/putyourlightson/craft-blitz/issues/257
        $this->_cache->set([
            self::KEY_PREFIX, (int)$siteUri->siteId, $siteUri->uri
        ], $value);
    }

    /**
     * @inheritdoc
     */
    public function deleteUris(array $siteUris)
    {
        if ($this->_cache === null) {
            return;
        }

        foreach ($siteUris as $siteUri) {
            // Cast the site ID to an integer to avoid an incorrect key
            // https://github.com/putyourlightson/craft-blitz/issues/257
            $this->_cache->delete([
                self::KEY_PREFIX, (int)$siteUri->siteId, $siteUri->uri
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAll()
    {
        if ($this->_cache === null) {
            return;
        }

        $this->_cache->flush();
    }

    /**
     * @inheritdoc
     */
    public function getUtilityHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('blitz/_drivers/storage/yii-cache/utility');
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('blitz/_drivers/storage/yii-cache/settings', [
            'driver' => $this,
        ]);
    }
}
