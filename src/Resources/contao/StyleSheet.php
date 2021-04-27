<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\StyleSheetModel;

/**
 * ApiStyleSheet augments StyleSheetModel for the API.
 */
class ApiStyleSheet extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the ModuleModel
     */
    public function __construct($id)
    {
        $this->model = StyleSheetModel::findById($id);
    }

    public static function list()
    {
        $stylesheets = [];
        foreach(StyleSheetModel::findAll() as $stylesheet) {
            $stylesheets[] = new self($stylesheet->id);
        }
        return new ContaoJson($stylesheets);
    }
}
