<?php

namespace DieSchittigs\ContaoContentApiBundle;

use DieSchittigs\ContaoContentApiBundle\ApiStyle;

use Contao\StyleSheetModel;

/**
 * ApiStyleSheet augments StyleSheetModel for the API.
 */
class ApiStyleSheet extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int $id id of the StyleSheetModel
     */
    public function __construct($id)
    {
        $this->model = StyleSheetModel::findById($id);
        $this->styles = ApiStyle::list($id);
    }


    public static function list($pid)
    {
        $stylesheets = [];

        $dbStylesheets = $pid > 0 ? StyleSheetModel::findByPid($pid) : StyleSheetModel::findAll();

        foreach ($dbStylesheets as $stylesheet) {
            $stylesheets[] = new self($stylesheet->id);
        }

        return $stylesheets;
    }

    public static function listAction($pid)
    {
        return new ContaoJson(self::list($pid));
    }
}
