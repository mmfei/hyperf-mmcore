<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Mangatoon\Hyperf\Extend\Controller\Admin;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Utils\ApplicationContext;
use MmCore\Controller\NeedLoginController;

class SimpleCrudController extends NeedLoginController
{
    /**
     * @var Model
     */
    public $model_class;

    protected $admin_config;

    protected $__crud_key;

    protected $per_page = 10;

    public function __construct()
    {
        $this->admin_config = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('admin_controller');
        $this->init();
    }

    public function init()
    {
        $this->__crud_key = $this->request->input('__crud_key');
        if ($this->__crud_key) {
            $this->model_class = $this->admin_config[$this->__crud_key]['model'] ?? '';
        }
    }

    public function list()
    {
        $this->init();
        $query = $this->model_class::query();
        if (method_exists($this, 'crudSetQueryForList')) {
            $this->crudSetQueryForList($query);
        }
        $this->orderListQueryByDefault($query);
        $paginator = $query->paginate($this->per_page);
        if (method_exists($this, 'crudBatchRenderModelForList')) {
            $paginator = $this->crudBatchRenderModelForList($paginator);
        }
        $data = [];
        foreach ($paginator as $item) {
            if (method_exists($this, 'crudRenderModelForList')) {
                $data[] = $this->crudRenderModelForList($item);
            } else {
                $data[] = $item;
            }
        }
        return [
            'data' => $data,
            'paginator' => $this->formatPaginatorForJSONOutput($paginator),
        ];
    }

    public function formatPaginatorForJSONOutput(LengthAwarePaginatorInterface $paginator): stdClass
    {
        $page_count = floor($paginator->total() / $paginator->perPage());
        if ($paginator->total() % $paginator->perPage() !== 0) {
            ++$page_count;
        }
        $next_page = $paginator->hasMorePages() ? $paginator->currentPage() + 1 : 0;
        $json = new stdClass();
        $json->total_count = $paginator->total();
        $json->items_count_per_page = $paginator->perPage();
        $json->page_count = $page_count;
        $json->next_page = $next_page;
        return $json;
    }

    public function create()
    {
        $this->init();

        $primary_key = $this->getPrimaryColumnName();
        $fields = $this->getFields();

        $data = [];
        foreach ($fields as $k) {
            $key = $k['prop'] ?? '';
            if ($this->isUsesTimestamps() && in_array($key, [Model::CREATED_AT, Model::UPDATED_AT])) {
                continue;
            }

            if ($key != $primary_key) {
                $v = $this->request->input($key);
                if (method_exists($this->model_class, 'transformModelValueByInput')) {
                    $v = $this->model_class::transformModelValueByInput([$key => $v === null ? '' : $v])[$key];
                }

                if (isset($v)) {
                    $data[$key] = $v;
                }
            }
        }
        $return = false;
        if (! empty($data)) {
            $model = new $this->model_class();
            $return = $this->saveModel($model, $data);
        }
        return ['status' => $return ? 'success' : 'failed'];
    }

    public function update()
    {
        $this->init();

        $primary_key = $this->getPrimaryColumnName();
        $fields = $this->getFields();
        $data = [];
        foreach ($fields as $k) {
            $key = $k['prop'] ?? '';
            if ($this->isUsesTimestamps() && in_array($key, [Model::CREATED_AT, Model::UPDATED_AT])) {
                continue;
            }

            if ($key != $primary_key) {
                $data[$key] = $this->request->input($key);
                if (method_exists($this->model_class, 'transformModelValueByInput')) {
                    $data[$key] = $this->model_class::transformModelValueByInput([$key => $data[$key] === null ? '' : $data[$key]])[$key];
                }
            }
        }
        $return = false;

        $value = $this->request->input($primary_key);
        $model = $this->model_class::find($value);
        if (! empty($data) && ! empty($model)) {
            $return = $this->saveModel($model, $data);
        }
        return ['status' => $return ? 'success' : 'failed'];
    }

    public function delete()
    {
        $this->init();

        $primary_key = $this->getPrimaryColumnName();
        $value = $this->request->input($primary_key);
        $model = $this->model_class::find($value);
        $return = false;
        if (! empty($model)) {
            try {
                $return = (bool) $model->delete();
                if ($return) {
                    $callback = $this->admin_config[$this->__crud_key]['delete']['callback'] ?? [];
                    if ($callback) {
                        $class = $callback[0] ?? [];
                        if ($class) {
                            call_user_func($class, $model);
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return ['status' => $return ? 'success' : 'failed'];
    }

    public function getAggregate()
    {
        $return = [];
        $this->init();
        $query = $this->model_class::query();
        if (method_exists($this, 'crudSetQueryForList')) {
            $this->crudSetQueryForList($query);
        }
        if ($this->__crud_key) {
            $aggregate = $this->admin_config[$this->__crud_key]['list_aggregate']['list'] ?? [];
            $select_list = [];
            foreach ($aggregate as $arr) {
                $prop = ($arr['prop'] ?? '');
                $aggregate_type = $arr['aggregate_type'] ?? '';
                if ($prop && $aggregate_type) {
                    $name = $arr['field_name'] ?? $prop;
                    $name = $name ? $name : $prop;
                    $select_list[] = "{$aggregate_type}({$name}) as {$prop}";
                }
            }
            if ($select_list) {
                $select_raw = join(',', $select_list);
                if ($select_list) {
                    $group = $this->admin_config[$this->__crud_key]['list_aggregate']['group'] ?? [];
                    if ($group) {
                        $query->groupBy($group);
                    }
                    $data = $query->selectRaw($select_raw)->get();
                    if ($data) {
                        $list = ($data[0] ?? []);

                        foreach ($aggregate as $arr) {
                            $prop = ($arr['prop'] ?? '');
                            $arr['value'] = $list->{$prop} ?? 0;
                            $return[] = $arr;
                        }
                    }
                }
            }
        }
        return $return;
    }

    public function getSetting()
    {
        $this->init();
        return [
            'model' => $this->admin_config[$this->__crud_key]['model'] ?? '',
            'title' => $this->admin_config[$this->__crud_key]['title'] ?? '',
            'is_write_able' => $this->admin_config[$this->__crud_key]['is_write_able'] ?? false,
            'btn_add_show' => $this->admin_config[$this->__crud_key]['btn_add_show'] ?? false,
            'btn_edit_show' => $this->admin_config[$this->__crud_key]['btn_edit_show'] ?? false,
            'btn_delete_show' => $this->admin_config[$this->__crud_key]['btn_delete_show'] ?? false,
            'primary_key' => $this->getPrimaryColumnName() ?? '',
            'extra' => $this->admin_config[$this->__crud_key]['extra'] ?? [],
        ];
    }

    public function getSimpleCrudFields()
    {
        $this->init();

        return $this->getFields();
    }

    public function getPrimaryColumnName()
    {
        return (new $this->model_class())->getKeyName() ?? 'id';
    }

    public function isUsesTimestamps()
    {
        if (isset($this->admin_config[$this->__crud_key]['use_timestamp'])) {
            return $this->admin_config[$this->__crud_key]['use_timestamp'];
        }

        return (new $this->model_class())->usesTimestamps() ?? false;
    }

    /**
     * 默认按照主键倒序排列列表.
     */
    protected function orderListQueryByDefault(Builder $query)
    {
        $query->orderByDesc($this->getPrimaryColumnName());
    }

    protected function crudSetQueryForList(Builder $query)
    {
        $order_list = $this->admin_config[$this->__crud_key]['list']['order_list'] ?? [];
        $filter = $this->admin_config[$this->__crud_key]['extra']['list']['filter'] ?? [];

        if ($order_list) {
            foreach ($order_list as $col_name => $sort_type) {
                $query->orderBy($col_name, $sort_type);
            }
        }

        if ($filter) {
            foreach ($filter as $arr) {
                $key = $arr['key'] ?? '';
                $op = $arr['op'] ?? '=';
                $value = $this->request->input($key, '');
//                if($key == '' || $op == '' || $value == '') continue;
                if (! is_numeric($value)) {
                    $value = str_replace("'", "\\'", $value);
                }
                switch ($op) {
                    case 'like':
                        $value = trim($value);
                        if ($value != '') {
                            $query->whereRaw("{$key} like '" . $value . "'");
                        }
                        break;
                    case 'in':
                        $value = explode(',', $value);
                        if ($value) {
                            $query->whereIn($key, $value);
                        }
                        break;
                    case 'between':
                        if ($value) {
                            $query->whereBetween($key, $value);
                        }
                        break;
                    default:
                        $value = trim($value);
                        if ($value != '') {
                            $query->whereRaw("{$key} {$op} '" . $value . "'");
                        }
                        break;
                }
            }
        }
        return $query;
    }

    private function getFields()
    {
        $return = [];
        $key = $this->request->input('__type');
        if (in_array($key, ['list', 'add', 'update'])) {
            if ($this->__crud_key) {
                $return = $this->admin_config[$this->__crud_key][$key]['fields'] ?? [];
                if (empty($return)) {
                    $model = $this->model_class;
                    $row = $model::query()->first();
                    if ($row) {
                        foreach (array_keys($row->attributesToArray()) as $k) {
                            if ($key == 'add' || $key == 'update') {
                                if ($this->isUsesTimestamps() && in_array($k, [Model::CREATED_AT, Model::UPDATED_AT])) {
                                    continue;
                                }
                            }
                            $return[] = [
                                'label' => $k,
                                'prop' => $k,
                                'attrs' => [],
                            ];
                        }
                    }
                } else {
                    //设置默认值
                    foreach ($return as &$item) {
                        if (! isset($item['attrs'])) {
                            $item['attrs'] = [
                                'width' => 100,
                                'style' => [],
                                'className' => '',
                            ];
                        }
                    }
                }
            }
        }
        return $return;
    }

    /**
     * 去掉fillable限制.
     *
     * @param $json
     * @return bool
     */
    private function saveModel(Model $model, $json)
    {
        foreach ($json as $k => $v) {
            $model->setAttribute($k, $v);
        }
        if (method_exists($this, 'crudUpdateModelAttributesBeforeSave')) {
            $this->crudUpdateModelAttributesBeforeSave($model);
        }
        $return = $model->save();
        if (method_exists($this, 'crudUpdateModelAttributesAfterSave')) {
            $this->crudUpdateModelAttributesAfterSave($model);
        }
        return $return;
    }
}
