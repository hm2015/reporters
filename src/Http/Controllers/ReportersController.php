<?php
namespace Encore\Admin\Reporters\Http\Controllers;

use Encore\Admin\Layout\Content;
use Encore\Admin\Reporters\Models\ExceptionModel;
use Encore\Admin\Reporters\Tracer\Parser;
use Illuminate\Routing\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
class ReportersController extends Controller
{
    use HasResourceActions;

    public function index(Content $content)
    {
        return $content
            ->title('异常')
            ->description('列表')
            ->body($this->grid());
    }

    protected function grid()
    {
        $grid = new Grid(new ExceptionModel());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id','ID')->sortable();
        $grid->column('type')->display(function ($type) {
            $path = explode('\\', $type);
            return array_pop($path);
        });
        $grid->column('code');
        $grid->column('message')->style('width:400px')->display(function ($message) {
            if (empty($message)) {
                return '';
            }
            return "<code>$message</code>";
        });
        $grid->column('request')->display(function () {
            $color = ExceptionModel::$methodColor[$this->method];
            return sprintf(
                '<span class="label bg-%s">%s</span><code>%s</code>',
                $color,
                $this->method,
                $this->path
            );
        });

        $grid->column('created_at');

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
        });

        $grid->disableExport();
        $grid->quickSearch('message');
        $grid->disableCreateButton();
        $grid->disableFilter();
        return $grid;
    }

    public function show($id,Content $content)
    {
        Admin::script('Prism.highlightAll();');
        $exception = ExceptionModel::findOrFail($id);
        $trace = "#0 {$exception->file}({$exception->line})\n";
        $frames = (new Parser($trace.$exception->trace))->parse();
        $cookies = json_decode($exception->cookies, true);
        $headers = json_decode($exception->headers, true);

        array_pop($frames);

        return $content->header('异常')
            ->description('异常详情')
            ->body(view('reporters::exception', compact('exception', 'frames', 'cookies', 'headers')));
    }

    public function destroy($id)
    {
        $ids = explode(',', $id);

        if (ExceptionModel::destroy(array_filter($ids))) {
            $data = [
                'status'  => true,
                'message' => trans('admin.delete_succeeded'),
            ];
        } else {
            $data = [
                'status'  => false,
                'message' => trans('admin.delete_failed'),
            ];
        }

        return response()->json($data);
    }
}
