<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rules;

/**
 * Controlador para gestionar usuarios del sistema
 * 
 * Solo accesible para administradores
 */
class UserController extends Controller
{
    /**
     * Muestra el listado de usuarios
     * 
     * @return View
     */
    public function index(): View
    {
        $users = User::orderBy('name')->paginate(15);
        $emailDomain = strtolower(substr(strrchr(auth()->user()->email, '@'), 1) ?: '');
        
        return view('users.index', compact('users', 'emailDomain'));
    }

    /**
     * Muestra el formulario para crear un nuevo usuario
     * 
     * @return View
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Almacena un nuevo usuario
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $adminDomain = strtolower(substr(strrchr(auth()->user()->email, '@'), 1) ?: '');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email_local' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:administrador,usuario',
        ], [
            'name.required' => 'El nombre es requerido.',
            'email_local.required' => 'El nombre de correo es requerido.',
            'email_local.regex' => 'El nombre de correo contiene caracteres no válidos.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'El rol es requerido.',
            'role.in' => 'El rol seleccionado no es válido.',
        ]);

        $email = $validated['email_local'] . '@' . $adminDomain;

        // Validate full email uniqueness
        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['email_local' => 'Este correo electrónico ya está registrado.'])->withInput();
        }

        $validated['email'] = $email;
        $validated['password'] = Hash::make($validated['password']);
        $validated['empresa_id'] = auth()->user()->empresa_id;
        unset($validated['email_local']);

        User::create($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Muestra el formulario para editar un usuario
     * 
     * @param User $user
     * @return View
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Actualiza un usuario existente
     * 
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $adminDomain = strtolower(substr(strrchr(auth()->user()->email, '@'), 1) ?: '');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email_local' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+$/'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:administrador,usuario',
        ], [
            'name.required' => 'El nombre es requerido.',
            'email_local.required' => 'El nombre de correo es requerido.',
            'email_local.regex' => 'El nombre de correo contiene caracteres no válidos.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role.required' => 'El rol es requerido.',
            'role.in' => 'El rol seleccionado no es válido.',
        ]);

        $email = $validated['email_local'] . '@' . $adminDomain;

        if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['email_local' => 'Este correo electrónico ya está registrado.'])->withInput();
        }

        $validated['email'] = $email;
        unset($validated['email_local']);

        // Solo actualizar la contraseña si se proporcionó una nueva
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina un usuario
     * 
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        // No permitir que un usuario se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }
}
