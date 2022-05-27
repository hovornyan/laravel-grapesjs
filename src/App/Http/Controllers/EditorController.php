<?php

namespace Dotlogics\Grapesjs\App\Http\Controllers;

use App\LandingPagesBlocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Dotlogics\Grapesjs\App\Editor\EditorFactory;
use Dotlogics\Grapesjs\App\Traits\EditorTrait;

class EditorController extends Controller
{
    use EditorTrait;

    public function __construct(Request $request){
        $model = $request->route()->parameters['model'] ?? null;

        if(!empty($model)){
            $request->route()->setParameter('model',  str_replace('-', '\\', $model));
        }
    }

    public function editor(Request $request, $model, $id)
    {
        return $this->show_gjs_editor($request, $model::findOrFail($id));
    }

    public function store(Request $request, $model, $id)
    {
        return $this->store_gjs_data($request, $model::findOrFail($id));
    }

    public function templates(Request $request)
    {
        $templatesPath = resource_path('views/vendor/grapesjs/templates');
        $otherBlocks = resource_path('views/vendor/grapesjs/gjs-blocks');

        if(!File::exists($templatesPath)) {
            $templatesPath = __DIR__ . '/../../../resources/views/templates';
        }

        if(!File::exists($otherBlocks)) {
            $otherBlocks = __DIR__ . '/../../../resources/views/gjs-blocks';
        }

        $templates = [];

        try {
            $db_templates =  LandingPagesBlocks::all();
            foreach ($db_templates as $template){
                $template->{'content'} = view("vendor.grapesjs.db-blocks.placeholder", compact('template'))->render();
            }
            $db_templates = $db_templates->toArray();
        }
        catch (\Exception $e){
            $db_templates = [];
        }
        $templates = $db_templates;


        foreach (File::allFiles($templatesPath) as $fileInfo) {
            $file_name = str_replace(".blade.php", "", $fileInfo->getBasename());
            $templates [] = [
                'category' => 'Templates',
                'id' => 'template-' . $fileInfo->getFilename(),
                'label' => Str::title(str_replace(["-"], " ", $file_name)),
                'content' => view("grapesjs::templates.{$file_name}")->render()
            ];
        }

        foreach (File::allFiles($otherBlocks) as $fileInfo) {
            $file_name = str_replace(".blade.php", "", $fileInfo->getBasename());
            $templates [] = [
                'category' => 'Blocks',
                'id' => 'block-' . $fileInfo->getFilename(),
                'label' => Str::title(str_replace(["-"], " ", $file_name)),
                'content' => view("grapesjs::gjs-blocks.{$file_name}")->render()
            ];
        }

        return $templates;
    }
}
