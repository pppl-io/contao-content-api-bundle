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
     * @param int $id id of the StyleModel
     */
    public function __construct($id)
    {
        $this->model = StyleModel::findById($id);
    }

    public static function list($pid)
    {
        $styles = [];

        $dbStyles = $pid > 0 ? StyleModel::findByPid($pid) : StyleModel::findAll();

        foreach ($dbStyles as $style) {
            $styles[] = new self($style->id);
        }

        return $styles;
    }

    public static function listAction($pid)
    {
        return new ContaoJson(self::list($pid));
    }
}
