<?php
namespace Encore\Admin\Reporters;

use Encore\Admin\Admin;
use Encore\Admin\Extension;
use Encore\Admin\Reporters\Models\ExceptionModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
class Reporters extends Extension
{
    public $name = 'reporters';

    public $views = __DIR__.'/../resources/views';

    public $assets = __DIR__.'/../resources/assets';

    public $menu = [
        'title' => 'Reporters',
        'path'  => 'exceptions',
        'icon'  => 'fa-bug',
    ];

    public $permission = [
        'name' => 'Reporters',
        'slug'  => 'ext.reporters',
        'path'  => 'exceptions*',
    ];

    /**
     * @var $instance
     */
    protected static $instance;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     * @return Reporters
     */
    public static function instance(Request $request)
    {
        if (!static::$instance instanceof self) {
            static::$instance = new static($request);
        }
        return static::$instance;
    }

    /**
     * Reporters constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Bootstrap this extension.
     */
    public static function boot()
    {
        $extension = static::instance(request());

        Admin::extend($extension->name, get_called_class());

        if ($extension->disabled()) {
            return false;
        }

        if (!empty($css = $extension->css())) {
            Admin::css($css);
        }

        if (!empty($js = $extension->js())) {
            Admin::js($js);
        }

        return true;
    }

    /**
     * @param \Exception $exception
     * @return bool
     */
    public static function report(\Exception $exception)
    {
        $reporter = self::instance(request());
        return $reporter->reportException($exception);
    }

    /**
     * @param \Exception $exception
     * @return bool
     */
    public function reportException(\Exception $exception)
    {
        $data = [
            // Request info.
            'method'    => $this->request->getMethod(),
            'ip'        => $this->request->getClientIps(),
            'path'      => $this->request->path(),
            'query'     => Arr::except($this->request->all(), ['_pjax', '_token', '_method', '_previous_']),
            'body'      => $this->request->getContent(),
            'cookies'   => $this->request->cookies->all(),
            'headers'   => Arr::except($this->request->headers->all(), 'cookie'),

            // Exception info.
            'exception' => get_class($exception),
            'code'      => $exception->getCode(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'message'   => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
        ];

        $data = $this->stringify($data);

        try {
            $result = $this->store($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * Convert all items to string.
     *
     * @param $data
     *
     * @return array
     */
    public function stringify($data)
    {
        return array_map(function ($item) {
            return is_array($item) ? json_encode($item, JSON_OBJECT_AS_ARRAY) : (string) $item;
        }, $data);
    }

    /**
     * Store exception info to db.
     *
     * @param array $data
     *
     * @return bool
     */
    public function store(array $data)
    {
        $exception = new ExceptionModel();

        $exception->type = $data['exception'];
        $exception->code = $data['code'];
        $exception->message = $data['message'];
        $exception->file = $data['file'];
        $exception->line = $data['line'];
        $exception->trace = $data['trace'];
        $exception->method = $data['method'];
        $exception->path = $data['path'];
        $exception->query = $data['query'];
        $exception->body = $data['body'];
        $exception->cookies = $data['cookies'];
        $exception->headers = $data['headers'];
        $exception->ip = $data['ip'];

        try {
            $result = $exception->save();
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }
}
