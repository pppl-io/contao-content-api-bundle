<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\LayoutModel;

/**
 * ApiLayout augments LayoutModel for the API.
 */
class ApiLayout extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the ModuleModel
     */
    public function __construct($id)
    {
        $this->model = LayoutModel::findByPk($id);
    }

    public static function list()
    {
        $layouts = [];
        foreach(LayoutModel::findAll() as $layout) {
            $layouts[] = new self($layout->id);
        }
        return $layouts;
    }
}
