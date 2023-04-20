<?php

namespace App\Plugins\PicPurge;

use Exceedone\Exment\Services\Plugin\PluginEventBase;
use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Enums\ValueType;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Plugin extends PluginEventBase
{
    protected $useCustomOption = true;

    /**
     * Plugin Trigger
     */
    public function execute()
    {
        $PLUGIN_DIR = 'plugins/PicPurge/public/';

        $limit = $this->plugin->getCustomOption('limit_size');
        $target = $this->plugin->getCustomOption('pic_target');

        // プラグインフォルダを取得
        $dir_path = $this->plugin->getFullPath();

        // カスタムテーブルの値のモデルインスタンスを取得する
        $query = $this->custom_table->getValueModel()->query();
        
        // idの降順でソートすることで、idが最大のデータを取得する
        $query->orderBy('id', 'desc');

        // クエリ結果の最初の行を返す(最新レコード)
        $newest_record = $query->first();

        // カスタム列の値(ファイルパス)とそのサイズを取得
        $file_path = base_path() . '/storage/app/admin/' . $newest_record->getValue($target);
        $file_size = filesize($file_path);

        //差し替え用の画像ファイルがなければ生成
        /*
        if (Storage::exists($dir_path . '/public/error.jpg') == false)
        {
            $errorImage = Image::canvas(200, 300, '#f0f0f0');
            $errorImage->line(50, 50, 200, 200, function ($draw) {
                $draw->color('#ff0000');
            });
            $errorImage->line(200, 50, 50, 200, function ($draw) {
                $draw->color('#ff0000');
            });
            $img = $errorImage->encode('jpg');
            Storage::put($dir_path . '/public/error.jpg', $img);

            $errorImage->destroy();

        }
        */

        //ファイル制限の対象であればerror.jpgと差し替え
        if($limit * 1024 < $file_size)
        {
            $deleted_record = $query->delete();

            return [
                'result' => false,
                'swaltext' => 'サイズ制限(' . $limit . 'KB)を超えたため削除されました。' . '(' . intval($file_size / 1024) . ' KB)',
            ];
    
        }

        return true;

    }

    /**
     * @param [type] $form
     * @return void
     */
    public function setCustomOptionForm(&$form)
    {

        $form->text('pic_target', 'カスタム列名')
            ->help('制限対象のカスタム列名(列種類：画像)を記入します。');

        $form->number('limit_size', '制限容量(KB)')
            ->help('ここで指定したファイルサイズを超えると、そのレコードが削除されます。');

    }
}
