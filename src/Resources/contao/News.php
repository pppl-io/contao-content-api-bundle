<?php

namespace DieSchittigs\ContaoContentApiBundle;


use Contao\NewsModel;

/**
 * ApiTheme augments ThemeModel for the API.
 */
class ApiNews extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the News
     */
    public function __construct($id)
    {
        $this->model = NewsModel::findById($id);
    }

    public static function list($pid = null)
    {
        $news = [];

        $dbNews = $pid !== null ? NewsModel::findByPid($pid) : NewsModel::findAll();

        foreach ($dbNews as $sNews) {
            $news[] = new self($sNews->id);
        }

        return $news;
    }

    public static function listAction($pid = null)
    {
        return new ContaoJson(self::list($pid));
    }
}
