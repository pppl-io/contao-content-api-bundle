<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\ThemeModel;

/**
 * ApiLayout augments LayoutModel for the API.
 */
class ApiTheme extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the ModuleModel
     */
    public function __construct($id)
    {
        $this->model = ThemeModel::findById($id);
    }

    public static function list()
    {
        $themes = [];
        foreach(ThemeModel::findAll() as $theme) {
            $themes[] = new self($theme->id);
        }
        return new ContaoJson($themes);
    }
}
