<?php

namespace Jonassiewertsen\StatamicButik\Tests\Checkout;

use Illuminate\Support\Facades\Session;
use Jonassiewertsen\StatamicButik\Checkout\Customer;
use Jonassiewertsen\StatamicButik\Checkout\Cart;
use Jonassiewertsen\StatamicButik\Http\Models\Product;
use Jonassiewertsen\StatamicButik\Tests\TestCase;

class CheckoutDeliveryTest extends TestCase
{
    protected $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->setCountry();
        $this->product = create(Product::class)->first();
        Cart::add($this->product->slug);
    }

// Failing in GitHub actions. Why?
//    /** @test */
//    public function the_user_will_be_redirected_without_any_products()
//    {
//        Cart::clear();
//
//        $this->get(route('butik.checkout.delivery', $this->product))
//            ->assertRedirect(route('butik.cart'));
//    }

    /** @test */
    public function the_product_information_will_be_displayed_without_saved_customer_data()
    {
        $this->withoutExceptionHandling();

        $this->get(route('butik.checkout.delivery', $this->product))
            ->assertSee('Checkout');
    }

    /** @test */
    public function user_data_will_be_saved_inside_the_session()
    {
        $this->withoutExceptionHandling();
        $this->post(route('butik.checkout.delivery'), (array)$this->createUserData())
            ->assertSessionHas('butik.customer');
    }

    /** @test */
    public function a_country_is_required()
    {
        $data = $this->createUserData('country', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('country');
    }

    /** @test */
    public function a_country_cant_be_to_long()
    {
        $data = $this->createUserData('country', str_repeat('a', 51));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('country');
    }

    /** @test */
    public function a_name_is_required()
    {
        $data = $this->createUserData('name', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_name_cant_be_to_short()
    {
        $data = $this->createUserData('name', str_repeat('a', 4));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_name_cant_be_to_long()
    {
        $data = $this->createUserData('name', str_repeat('a', 51));
        $this->post(route('butik.checkout.delivery', $this->product), (array)$data)
            ->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_mail_address_is_required()
    {
        $data = $this->createUserData('mail', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('mail');
    }

    /** @test */
    public function a_mail_address_bust_be_a_mail_address()
    {
        $data = $this->createUserData('mail', 'jonas');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('mail');
    }

    /** @test */
    public function address_line_1_is_required()
    {
        $data = $this->createUserData('address1', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('address1');
    }

    /** @test */
    public function address_line_1_cant_be_to_long()
    {
        $data = $this->createUserData('address1', str_repeat('a', 81));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('address1');
    }

    /** @test */
    public function address_line_2_is_optional()
    {
        $data = $this->createUserData('address2', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function address_line_2_cant_be_to_long()
    {
        $data = $this->createUserData('address2', str_repeat('a', 81));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('address2');
    }

    /** @test */
    public function city_is_required()
    {
        $data = $this->createUserData('city', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('city');
    }

    /** @test */
    public function city_2_cant_be_to_long()
    {
        $data = $this->createUserData('city', str_repeat('a', 81));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('city');
    }

    /** @test */
    public function state_region_is_optional()
    {
        $data = $this->createUserData('stage_region', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function state_region_cant_be_to_long()
    {
        $data = $this->createUserData('state_region', str_repeat('a', 81));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('state_region');
    }

    /** @test */
    public function zip_is_required()
    {
        $data = $this->createUserData('zip', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('zip');
    }

    /** @test */
    public function zip_cant_be_to_long()
    {
        $data = $this->createUserData('zip', str_repeat('a', 21));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('zip');
    }

    /** @test */
    public function phone_is_optional()
    {
        $data = $this->createUserData('phone', '');
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasNoErrors();
    }

    /** @test */
    public function phone_cant_be_to_long()
    {
        $data = $this->createUserData('phone', str_repeat('a', 51));
        $this->post(route('butik.checkout.delivery'), (array)$data)
            ->assertSessionHasErrors('phone');
    }

    /** @test */
    public function existing_data_from_the_session_will_be_passed_to_the_delivery_view()
    {
        Session::put('butik.customer', new Customer($this->createUserData()));
        $page     = $this->get(route('butik.checkout.delivery', $this->product))->content();
        $customer = session('butik.customer');

        $this->assertStringContainsString($customer->name, $page);
        $this->assertStringContainsString($customer->mail, $page);
        $this->assertStringContainsString($customer->address1, $page);
        $this->assertStringContainsString($customer->address2, $page);
        $this->assertStringContainsString($customer->city, $page);
        $this->assertStringContainsString($customer->zip, $page);
    }

    /** @test */
    public function after_a_valid_form_the_user_will_be_redirected_to_the_payment_page()
    {
        $this->post(route('butik.checkout.delivery'), (array)$this->createUserData())
            ->assertRedirect(route('butik.checkout.payment'));
    }

    private function createUserData($key = null, $value = null)
    {
        $customer = [
            'country'      => 'Germany',
            'name'         => 'John Doe',
            'mail'         => 'johndoe@mail.de',
            'address1'     => 'Main Street 2',
            'address2'     => '',
            'city'         => 'Flensburg',
            'state_region' => '',
            'zip'          => '24579',
            'phone'        => '013643-23837',
        ];

        if ($key !== null || $value !== null) {
            $customer[$key] = $value;
        }

        return $customer;
    }
}
