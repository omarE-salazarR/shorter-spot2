<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Url;
use Illuminate\Validation\ValidationException;

/**
 * Class shorterController
 * 
 * This controller manages shortened URLs and their redirections. It handles
 * creating, storing, and retrieving shortened URLs, as well as redirecting
 * users to the original URLs based on the shortened version.
 *
 * @package App\Http\Controllers
 */

class shorterController extends Controller
{
    /**
     * Creates the shortened link.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'url' => 'required|url',
                'domain' => 'required',
            ]);
            $consultUrl = Url::where('original_url', $request->input('url'))
                                    ->where('short_url', 'like', $request->input('domain').'%')
                                    ->first();
            if($consultUrl){
               return response()->json(['short_url' => $consultUrl->short_url]);
            }else{
                $url = new Url();
                $url->original_url = $request->input('url');
                $url->short_url = $request->input('domain').'/'.Str::random(8);
                if($url->save()){
                    return response()->json(['short_url' => $url->short_url]);
                }else{
                    throw new \Exception('Error en la creación del link, intente nuevamente');
                }
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 500);
        }
    }

    /**
     * Validates if the requested link has an original URL.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirect(Request $request)
    {
        $result= ['status' => 'success', 'url' => '', 'errors' => ''];
        try {
            $request->validate([
                'url' => 'required|url',
            ]);
            $url = Url::where('short_url', $request->url)->first();
            if($url){
                $result['url'] = $url->original_url;
            }else{
                throw new \Exception('No existe url');
            }
        } catch (ValidationException $e) {
            $result['status'] = 'fail';
            $result['errors'] = $e->errors();
        } catch (\Exception $e) {
            $result['status'] = 'fail';
            $result['errors'] = $e->getMessage();
        }
        return $result;
    }
}
