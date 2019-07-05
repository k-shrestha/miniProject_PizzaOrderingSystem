<?php

namespace App\Http\Controllers;

use Auth;
use App\Item;
use App\Size;
use App\Order;
use App\Address;
use App\Topping;
use App\Facades\Cart;
use App\ItemToppingPivot;
use Illuminate\Http\Request;
use App\Http\Requests\AddToBasketRequest;

class BasketController extends Controller
{

	public function __construct()
	{
		$this->middleware('auth')->except([
			'add', 'remove', 'update', 'index'
		]);
	}

	public function add(AddToBasketRequest $request)
	{
		$item = Item::find($request->item);
		$size = isset($request->size) ? Size::find($request->size) : null;
		$toppings = null;

		if (isset($request->toppings)) {
			$toppings = array();

			foreach ($request->toppings as $toppingId)
				array_push($toppings, Topping::find($toppingId));
		}

		Cart::add($item, $size, $toppings);
		
		return response('success', 200);
	}

	public function remove(Request $request)
	{
		Cart::remove($request->hash);

		return back();
	}

	public function update($hash, Request $request)
	{
		$this->validate($request, [
			'quantity' => 'required|numeric|min:1'
		]);

		Cart::setQuantity($hash, $request->quantity);

		return response('success', 200);
	}

	public function index()
	{
		return view('basket.index');
	}

	public function deliveryForm()
	{
		if (!Cart::all()->count())
			return redirect('/')->with('error', 'Your basket is empty.');

		return redirect('/basket/payment');
	}



	public function paymentForm()
	{

		return view('basket.payment');
	}


	private function createOrderFromSession()
	{
		$address =  null;

		$order = Auth::user()->orders()->save(
			Order::make([
				'address_id' => $address,
                'total_amount' => Cart::total()
			])
		);

		Cart::all()->each(function($cartItem, $hash) use(&$order) {

			if (!$cartItem->getToppings()) { //If no toppings

				$order->itemToppingPivots()->save(
					ItemToppingPivot::create([
						'item_id' => $cartItem->getItem()->id,
						'topping_id' => null
					]), [
						'quantity' => $cartItem->getQuantity(),
						'size_id' => $cartItem->getSize()
					]
				);

			} else {

				foreach ($cartItem->getToppings() as $topping) {
					$order->itemToppingPivots()->save(
						ItemToppingPivot::create([
							'item_id' => $cartItem->getItem()->id,
							'topping_id' => $topping->id
						]), [
							'quantity' => $cartItem->getQuantity(),
							'size_id' => $cartItem->getSize()->id
						]
					);
				}
			}
		});

        return $order;
	}

    public function purchase(Request $request)
    {
        $order = $this->createOrderFromSession();


        //Cart::destroy();

        return view('basket.purchaseSuccessfull')->withOrder($order);
    }
}
