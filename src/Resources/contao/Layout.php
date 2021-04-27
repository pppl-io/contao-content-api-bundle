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
     * @param int $id id of the LayoutModel
     */
    public function __construct($id)
    {
        $this->model = LayoutModel::findById($id);
    }

    public static function list($pid)
    {
        $layouts = [];

        $dbLayouts = $pid > 0 ? LayoutModel::findByPid($pid) : LayoutModel::findAll(); 

        foreach($dbLayouts as $layout) {
            $layouts[] = new self($layout->id);
        }

        return $layouts;
    }

    public static function listAction($pid)
    {
        return new ContaoJson(self::list($pid));
    }
}
