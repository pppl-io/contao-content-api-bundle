<?php

namespace DieSchittigs\ContaoContentApiBundle;


use DieSchittigs\ContaoContentApiBundle\ApiNews;

use Contao\NewsArchiveModel;

/**
 * ApiTheme augments ThemeModel for the API.
 */
class ApiNewsArchive extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the NewsArchive
     */
    public function __construct($id)
    {
        $this->model = NewsArchiveModel::findById($id);
        $this->news = ApiNews::list($this->id);
    }

    public static function list()
    {
        $newsArchives = [];

        foreach (NewsArchiveModel::findAll() as $newsArchive) {
            $newsArchives[] = new self($newsArchive->id);
        }

        return $newsArchives;
    }

    public static function listAction()
    {
        return new ContaoJson(self::list());
    }
}
