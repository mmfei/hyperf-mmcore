<?php

declare(strict_types=1);
/**
 * This file is part of EC.
 *
 * @link     https://topplayable.com
 * @document https://doc.ec.toplayable.com
 * @contact  it@topplayable.com
 */
namespace App\Command;

use Exception;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use OpenApi\Analyser;
use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\MediaType;
use OpenApi\Annotations\Parameter;
use OpenApi\Annotations\Property;
use OpenApi\Annotations\Response;
use OpenApi\Annotations\Schema;
use OpenApi\Context;
use Symfony\Component\Console\Input\InputOption;
use function OpenApi\scan;
use const OpenApi\UNDEFINED;

/**
 * @Command
 */
class SwaggerCommand extends HyperfCommand
{
    protected $name = 'swagger-code';

    public function handle()
    {
        $app_name  = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('app_name');
        $path      = $this->input->getOption('path');
        $outputDir = $this->input->getOption('output');
        $format    = $this->input->getOption('format');
//        $project_token = $this->input->getOption('project_token');
        if (empty($app_name)) {
            return false;
        }
        Analysis::registerProcessor(function (Analysis $analysis) {
            foreach ($analysis->openapi->paths ?? [] as $path) {
                echo "----------------------------\n";
                echo $path->path . "\n";

                $method_obj = UNDEFINED;
                $default_param_in = UNDEFINED;  //使用@param定义的参数默认行为
                if ($path->get != UNDEFINED) {
                    $method_obj = $path->get;
                    $default_param_in = 'query';
                } elseif ($path->post != UNDEFINED) {
                    $method_obj = $path->post;
                    $default_param_in = 'formData';
                }
                $parameter_keys = [];
                if ($method_obj != UNDEFINED) {
                    if (empty($method_obj->description) || $method_obj->description == UNDEFINED) {
                        $method_obj->description = '';
                    }
                    if ($method_obj->parameters == UNDEFINED) {
                        $method_obj->parameters = [];
                    }
                    foreach ($method_obj->parameters as $parameter) {
                        $parameter_keys[] = $parameter->name . '_' . $parameter->in;
                    }
                }

                //解析params
                foreach (explode("\n", $path->_context->comment) as $line) {
                    if (preg_match('/\@param +([a-z]+) +\$([a-zA-Z_0-9]+) *(.*)/', $line, $matches)) {
                        if (in_array($matches[2] . '_' . $default_param_in, $parameter_keys)) { //原生定义方式优先级更高
                            continue;
                        }
                        $type = new Schema(['required' => ['required'], 'type' => $matches[1]]);
                        $parameter = new Parameter([
                            'name'        => $matches[2],
                            'in'          => $default_param_in,
                            'description' => $matches[3],
                            'schema'      => $type,
                        ]);
                        $method_obj->parameters[] = $parameter;
                    }
                }
                $line_comments = [];
                $response_json_str = '';
                $response_json_str_with_comment = '';
                $api_description = '';
                //解析response json
                if (preg_match('/json: *(\{.*(?R)*\})/s', $path->_context->comment, $matches)) {
                    $document = str_replace("\t", '  ', $matches[1]);
                    $document = '     * ' . $document;
                    $depth_str = '';
                    foreach (explode("\n", $document) as $line) {
                        //去除*号
                        $line = preg_replace('/^\s*\*/', '', $line);
                        $response_json_str_with_comment .= $line . "\n";
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }
                        //计算深度
                        if (preg_match('/\"([a-zA-Z-_]+)\"\s*\:/', $line, $matches)) {
                            $depth_str .= '#';
                        }
                        //匹配注释
                        if (preg_match('/\s+\/\/.*/', $line, $matches)) {
                            //获取注释
                            $line_comment = preg_replace('/\s*\/\/\s*/', '', $matches[0]);
                            //去除注释
                            $line = str_replace($matches[0], '', $line);
                            //匹配属性
                            if (preg_match('/\"([a-zA-Z-_]+)\"\s*\:/', $line, $matches)) {
                                $attr = $matches[1];
                                $line_comments[$attr . $depth_str] = $line_comment;
                            }
                        }
                        $response_json_str .= $line . "\n";
                    }
                }

                //获取接口描述信息
                if (preg_match('/\/\*\*(.*?)\*\s+\@OA/s', $path->_context->comment, $matches)) {
                    if (! empty($matches)) {
                        $api_description = $matches[1];
                        $api_description = preg_replace('/ +\*/', '', $api_description);
                    }
                }

                $depth_str = '';
                $fn = function ($data) use (&$fn, &$depth_str, $line_comments) {
                    $properties = [];
                    foreach ($data as $k => $v) {
                        $depth_str .= '#';
                        $property = new Property([]);
                        $property->property = $k;
                        $property->description = $line_comments[$k . $depth_str] ?? '';
                        if (is_array($v)) {
                            $property->type = 'array';
                            $items = new Items([]);
                            $items->type = 'object';
                            $items->properties = $fn($v[0]);
                            $property->items = $items;
                        } elseif (is_object($v)) {
                            $property->type = 'object';
                            $property->properties = $fn($v);
                        }
//                            $property->type = 'string';

                        $properties[] = $property;
                    }
                    return $properties;
                };

                if (! empty(json_decode($response_json_str))) {
                    $schema = new Schema([]);
                    $schema->properties = $fn(json_decode($response_json_str));

                    $media_type = new MediaType([]);
                    $media_type->mediaType = 'application/json';
                    $media_type->schema = $schema;

                    $resp = new Response([]);
                    $resp->response = '200';
                    $resp->description = 'Success';
                    $resp->content = [$media_type];

                    if ($method_obj != UNDEFINED) {
                        $method_obj->responses = [$resp];
                    }
                } else {
                    echo "xxxxxxxxxx json format error xxxxxxxxxx\n";
                    echo "filename: {$path->_context->filename}\n";
                }
//                echo "----------------------------\n";

                if ($method_obj != UNDEFINED) {
                    if (! empty($method_obj->description)) {
                        $method_obj->description .= '<br/><br/>';
                    }
                    if (! empty($api_description) || ! empty($response_json_str_with_comment)) {
                        $method_obj->description .= '示例：<br/><pre><code>'; //使用描述显示返回内容
                        if (! empty($api_description)) {
                            $method_obj->description .= $api_description . "\n";
                        }
                        if (! empty($response_json_str_with_comment)) {
                            $method_obj->description .= $response_json_str_with_comment;
                        }
                        $method_obj->description .= '</code></pre>';
                    }
                }

                if ($path->path !== UNDEFINED) {
                    continue;
                }
                switch ($path) {
                    case isset($path->get):
                        $operationId = $path->get->operationId;
                        if (strpos($operationId, '::') !== false) {
                            [$controller, $action] = explode('::', $operationId);
                            // @TODO Retrieve the path according to controller and action name, and then set the path to $path->path.
                        }
                        break;
                }
            }
        });

        $scanner     = scan($path);
        $file_name   = 'openapi-' . $app_name . '.' . $format;
        $destination = $outputDir . $file_name;
        $scanner->saveAs($destination, $format);
        $this->info(sprintf('[INFO] Written to %s successfully.', $destination));
//        if (!empty($project_token)) {
//            $yapi_import = [
//                'type' => 'swagger',
//                'token' => $project_token,
//                'file' => $file_name,
//                'merge' => 'mergin',
//                'server' => 'http://apidoc.mangatoon.mobi/',
//            ];
//            $yapi_import_file_path = $outputDir . 'yapi-import-' . $app_name . '.json';
//            if (file_put_contents($yapi_import_file_path, json_encode($yapi_import)) === false) {
//                throw new Exception('Failed to saveAs ' . $yapi_import_file_path);
//            }
//        }
        $this->line('已经成功生成文档! ' . $destination, 'info');
    }

    protected function getOptions(): array
    {
        return [
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'The path that needs scan.', 'app/'],
            ['output', 'o', InputOption::VALUE_OPTIONAL, 'Path to store the generated documentation.', './'],
            ['format', 'f', InputOption::VALUE_OPTIONAL, 'The format of the generated documentation, supports yaml and json.', 'yaml'],
            //            ['project_token', 'pt', InputOption::VALUE_OPTIONAL, 'The Yapi projcet token.', ''],
        ];
    }

    /**
     * @param string $comment Contents of a comment block
     *
     * @return AbstractAnnotation[]
     */
    protected function parseComment($comment)
    {
        $analyser = new Analyser();
        $context  = Context::detect(1);
        return $analyser->fromComment("<?php\n/**\n * " . implode("\n * ", explode("\n", $comment)) . "\n*/", $context);
    }
}
