<?php  namespace LaravelAcl\Authentication\Controllers;

/**
 * Class UserController
 *
 * @author jacopo beschi jacopo@jacopobeschi.com
 */
use Illuminate\Support\MessageBag;
use LaravelAcl\Authentication\Exceptions\PermissionException;
use LaravelAcl\Authentication\Exceptions\ProfileNotFoundException;
use LaravelAcl\Authentication\Helpers\DbHelper;
use LaravelAcl\Authentication\Models\UserProfile;
use LaravelAcl\Authentication\Presenters\UserPresenter;
use LaravelAcl\Authentication\Services\UserProfileService;
use LaravelAcl\Authentication\Validators\UserProfileAvatarValidator;
use LaravelAcl\Library\Exceptions\NotFoundException;
use LaravelAcl\Authentication\Models\User;
use LaravelAcl\Authentication\Helpers\FormHelper;
use LaravelAcl\Authentication\Exceptions\UserNotFoundException;
use LaravelAcl\Authentication\Validators\UserValidator;
use LaravelAcl\Library\Exceptions\JacopoExceptionsInterface;
use LaravelAcl\Authentication\Validators\UserProfileValidator;
use View, Input, Redirect, App, Config;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;
use DB;
use LaravelAcl\Cars;
use Illuminate\Http\Request;
use Validator;
use Session;

class CarsController extends Controller 
{
    /**
     * @var \LaravelAcl\Authentication\Repository\SentryUserRepository
     */
    protected $user_repository;
    protected $user_validator;
    /**
     * @var \LaravelAcl\Authentication\Helpers\FormHelper
     */
    protected $form_helper;
    protected $profile_repository;
    protected $profile_validator;
    /**
     * @var use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
     */
    protected $auth;
    protected $register_service;
    protected $custom_profile_repository;
	protected $salesman;
	protected $manager;

    public function __construct(UserValidator $v, FormHelper $fh, UserProfileValidator $vp, AuthenticateInterface $auth)
    {
        $this->user_repository = App::make('user_repository');
        $this->user_validator = $v;
        $this->f = App::make('form_model', [$this->user_validator, $this->user_repository]);
        $this->form_helper = $fh;
        $this->profile_validator = $vp;
        $this->profile_repository = App::make('profile_repository');
        $this->auth = $auth;
        $this->register_service = App::make('register_service');
        $this->custom_profile_repository = App::make('custom_profile_repository');

		$logged_user = $this->auth->getLoggedUser();
		$json =$logged_user->user_profile()->first()->permissions;
		$user_json = json_decode($logged_user, true);
		$permissions = $user_json['permissions'];
		if (isset($permissions['_manager']))
		{
			$this->manager = $permissions['_manager'];
		}
		if (isset($permissions['_salesman']))
		{
			$this->salesman = $permissions['_salesman'];
		}
    }
	/**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
		if ($this->manager)
		{
			// get all the cars
			$cars = Cars::all();

			// load the view and pass the cars
			return View::make('cars.index')->with('cars', $cars);
			exit;
		}
		else
		{
			return Redirect::route("user.signup-success");
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
         return View::make('cars.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
	{
        // validate
        // read more on validation at http://laravel.com/docs/validation
        $rules = array(
            'name'       => 'required',
			'quantity' => 'required|numeric'
        );
        $validator = Validator::make(Input::all(), $rules);

        // process the login
        if ($validator->fails()) {
            return Redirect::to('cars/create')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            // store
            $cars = new Cars;
            $cars->name       = Input::get('name');
            $cars->quantity      = Input::get('quantity');
            $cars->note = Input::get('note');
            $cars->save();

            // redirect
            Session::flash('message', 'Successfully created cars!');
            return Redirect::to('cars');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
		$cars = Cars::find($id);

		// show the view and pass the cars to it
		return View::make('cars.show')
		->with('cars', $cars);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $cars = Cars::find($id);
        // show the edit form and pass the cars
        return View::make('cars.edit')
            ->with('cars', $cars);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
// validate
        // read more on validation at http://laravel.com/docs/validation
        $rules = array(
            'name'       => 'required',
			'quantity' => 'required|numeric'
        );
        $validator = Validator::make(Input::all(), $rules);

        // process the login
        if ($validator->fails()) {
            return Redirect::to('cars/' . $id . '/edit')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            // store
            $cars = Cars::find($id);
            $cars->name       = Input::get('name');
            $cars->quantity      = Input::get('quantity');
            $cars->note = Input::get('note');
            $cars->save();

            // redirect
            Session::flash('message', 'Successfully updated cars!');
            return Redirect::to('cars');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
		$cars = Cars::find($id);
		$cars->delete();

		// redirect
		Session::flash('message', 'Successfully deleted the cars!');
		return Redirect::to('cars');
    }
} 
