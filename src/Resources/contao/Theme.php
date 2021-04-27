<?php

namespace DieSchittigs\ContaoContentApiBundle;

use DieSchittigs\ContaoContentApiBundle\ApiStyleSheet;
use DieSchittigs\ContaoContentApiBundle\ApiLayout;
use DieSchittigs\ContaoContentApiBundle\File;

use Contao\ThemeModel;

/**
 * ApiTheme augments ThemeModel for the API.
 */
class ApiTheme extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the ThemeModel
     */
    public function __construct($id)
    {
        $this->model = ThemeModel::findById($id);
        $this->layouts = ApiLayout::list($id);
        $this->stylesheets = ApiStyleSheet::list($id);

        if ($this->folders) {
            $folders = unserialize($this->folders);
            if (is_array($folders)) {
                foreach ($folders as $k => $folder) {
                    $folders[$k] = new File($folder);
                }
            }
            $this->folders = $folders;
        }

        if ($this->screenshot) {
            $this->screenshot = new File($this->screenshot);
        }
    }

    public static function list()
    {
        $themes = [];

        foreach (ThemeModel::findAll() as $theme) {
            $themes[] = new self($theme->id);
        }

        return $themes;
    }

    public static function listAction()
    {
        return new ContaoJson(self::list());
    }
}
