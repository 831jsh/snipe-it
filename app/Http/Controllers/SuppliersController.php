<?php
namespace App\Http\Controllers;

use App\Helpers\Helper;
use Image;
use App\Models\AssetMaintenance;
use Input;
use Lang;
use App\Models\Supplier;
use Redirect;
use App\Models\Setting;
use Str;
use View;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests\ImageUploadRequest;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This controller handles all actions related to Suppliers for
 * the Snipe-IT Asset Management application.
 *
 * @version    v1.0
 */
class SuppliersController extends Controller
{
    /**
     * Show a list of all suppliers
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        // Grab all the suppliers
        $this->authorize('view', Supplier::class);

        // Show the page
        return view('suppliers/index');
    }


    /**
     * Supplier create.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $this->authorize('create', Supplier::class);
        return view('suppliers/edit')->with('item', new Supplier);
    }


    /**
     * Supplier create form processing.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ImageUploadRequest $request)
    {
        $this->authorize('create', Supplier::class);
        // Create a new supplier
        $supplier = new Supplier;
        // Save the location data
        $supplier->name                 = request('name');
        $supplier->address              = request('address');
        $supplier->address2             = request('address2');
        $supplier->city                 = request('city');
        $supplier->state                = request('state');
        $supplier->country              = request('country');
        $supplier->zip                  = request('zip');
        $supplier->contact              = request('contact');
        $supplier->phone                = request('phone');
        $supplier->fax                  = request('fax');
        $supplier->email                = request('email');
        $supplier->notes                = request('notes');
        $supplier->url                  = $supplier->addhttp(request('url'));
        $supplier->user_id              = Auth::id();
        $supplier = $request->handleImages($supplier,600, public_path().'/uploads/suppliers');


        if ($supplier->save()) {
            return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.create.success'));
        }
        return redirect()->back()->withInput()->withErrors($supplier->getErrors());
    }

    /**
     * Supplier update.
     *
     * @param  int  $supplierId
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($supplierId = null)
    {
        $this->authorize('edit', Supplier::class);
        // Check if the supplier exists
        if (is_null($item = Supplier::find($supplierId))) {
            // Redirect to the supplier  page
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.does_not_exist'));
        }

        // Show the page
        return view('suppliers/edit', compact('item'));
    }


    /**
     * Supplier update form processing page.
     *
     * @param  int  $supplierId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($supplierId = null, ImageUploadRequest $request)
    {
        $this->authorize('edit', Supplier::class);
        // Check if the supplier exists
        if (is_null($supplier = Supplier::find($supplierId))) {
            // Redirect to the supplier  page
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.does_not_exist'));
        }

        // Save the  data
        $supplier->name                 = request('name');
        $supplier->address              = request('address');
        $supplier->address2             = request('address2');
        $supplier->city                 = request('city');
        $supplier->state                = request('state');
        $supplier->country              = request('country');
        $supplier->zip                  = request('zip');
        $supplier->contact              = request('contact');
        $supplier->phone                = request('phone');
        $supplier->fax                  = request('fax');
        $supplier->email                = request('email');
        $supplier->url                  = $supplier->addhttp(request('url'));
        $supplier->notes                = request('notes');
        $supplier = $request->handleImages($supplier,600, public_path().'/uploads/suppliers');

        if ($supplier->save()) {
            return redirect()->route('suppliers.index')->with('success', trans('admin/suppliers/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($supplier->getErrors());

    }

    /**
     * Delete the given supplier.
     *
     * @param  int  $supplierId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($supplierId)
    {
        $this->authorize('delete', Supplier::class);
        if (is_null($supplier = Supplier::with('asset_maintenances', 'assets', 'licenses')->withCount('asset_maintenances as asset_maintenances_count','assets as assets_count','licenses as licenses_count')->find($supplierId))) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.not_found'));
        }


        if ($supplier->assets_count > 0) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.delete.assoc_assets', ['asset_count' => (int) $supplier->assets_count]));
        }

        if ($supplier->asset_maintenances_count > 0) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.delete.assoc_maintenances', ['asset_maintenances_count' => $supplier->asset_maintenances_count]));
        }

        if ($supplier->licenses_count > 0) {
            return redirect()->route('suppliers.index')->with('error', trans('admin/suppliers/message.delete.assoc_licenses', ['licenses_count' => (int) $supplier->licenses_count]));
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success',
            trans('admin/suppliers/message.delete.success')
        );


    }


    /**
     *  Get the asset information to present to the supplier view page
     *
     * @param null $supplierId
     * @return \Illuminate\Contracts\View\View
     * @internal param int $assetId
     */
    public function show($supplierId = null)
    {
        $supplier = Supplier::find($supplierId);

        if (isset($supplier->id)) {
                return view('suppliers/view', compact('supplier'));
        }
        // Prepare the error message
        $error = trans('admin/suppliers/message.does_not_exist', compact('id'));

        // Redirect to the user management page
        return redirect()->route('suppliers.index')->with('error', $error);
    }

}
