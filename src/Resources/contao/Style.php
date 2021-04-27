<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\StyleModel;

/**
 * ApiStyle augments StyleModel for the API.
 */
class ApiStyle extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the ModuleModel
     */
    public function __construct($id)
    {
        $this->model = StyleModel::findById($id);
    }

    public static function list()
    {
        $styles = [];
        foreach(StyleModel::findAll() as $style) {
            $styles[] = new self($style->id);
        }
        return new ContaoJson($styles);
    }
}
