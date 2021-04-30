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

        $modules = unserialize($this->modules);

        if (is_array($modules)) {
            $modules = array_filter($modules, function ($module) {
                return $module["enable"] === 1;
            });
            $groupedModules = new \stdClass();
            foreach ($modules as $mod => $module) {

                if (!isset($groupedModules->{$module["col"]})) {
                    $groupedModules->{$module["col"]} = [];
                }

                if ($module["mod"] === "0") {
                    $groupedModules->{$module["col"]}[] = $module;
                    continue;
                }

                $dirtyModule = new ApiModule($module["mod"]);

                $dirtyModule->layoutPosition = $module["col"];

                $groupedModules->{$module["col"]}[] = $dirtyModule;
            }
            $this->modules = $groupedModules;
        }
    }

    public static function list($pid)
    {
        $layouts = [];

        $dbLayouts = $pid > 0 ? LayoutModel::findByPid($pid) : LayoutModel::findAll();

        foreach ($dbLayouts as $layout) {
            $layouts[] = new self($layout->id);
        }

        return $layouts;
    }

    public static function listAction($pid)
    {
        return new ContaoJson(self::list($pid));
    }
}
