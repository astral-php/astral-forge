<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Dao\UserDao;
use App\Events\RoleChanged;
use Controller\AbstractController;
use Core\Auth\Auth;
use Core\Auth\Role;
use Core\Events\EventDispatcher;
use Core\Exception\NotFoundException;
use Core\Http\Response;
use Core\Request;
use Core\Session;
use Core\View;

/**
 * Contrôleur d'administration — Gestion des rôles utilisateurs.
 *
 * Toutes les actions sont protégées par AdminMiddleware (déclaré dans routes.php).
 *
 * Règles de sécurité appliquées :
 *   - Un admin ne peut pas modifier son propre rôle.
 *   - Le dernier administrateur ne peut pas être rétrogradé.
 *   - Seuls les rôles valides (Role::all()) sont acceptés.
 */
final class UserController extends AbstractController
{
    public function __construct(
        View                    $view,
        private Request         $request,
        private UserDao         $userDao,
        private Session         $session,
        private Auth            $auth,
        private EventDispatcher $dispatcher,
    ) {
        parent::__construct($view);
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * GET /admin/users — tableau de bord de gestion des rôles.
     */
    public function index(): Response
    {
        $users      = $this->userDao->findAll();
        $adminCount = count($this->userDao->findByRole(Role::Admin));

        return $this->render('admin/users/index', [
            'title'      => 'Gestion des rôles',
            'users'      => $users,
            'roles'      => Role::all(),
            'adminCount' => $adminCount,
        ]);
    }

    /**
     * POST /admin/users/:id/role — met à jour le rôle d'un utilisateur.
     */
    public function updateRole(string $id): Response
    {
        $userId  = (int) $id;
        $newRole = trim((string) $this->request->input('role', ''));

        // ── Guard 1 : on ne peut pas modifier son propre rôle ────────────────
        if ($userId === $this->auth->id()) {
            $this->session->flash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirect('/admin/users');
        }

        // ── Guard 2 : rôle valide ─────────────────────────────────────────────
        if (!Role::isValid($newRole)) {
            $this->session->flash('error', "Rôle invalide : « {$newRole} ».");
            return $this->redirect('/admin/users');
        }

        // ── Guard 3 : ne pas rétrograder le dernier admin ────────────────────
        $user = $this->userDao->findById($userId);

        if ($user === null) {
            throw new NotFoundException("Utilisateur #{$userId} introuvable.");
        }

        if ($user->role === Role::Admin->value && $newRole !== Role::Admin->value) {
            $adminCount = count($this->userDao->findByRole(Role::Admin));

            if ($adminCount <= 1) {
                $this->session->flash(
                    'error',
                    "Impossible de rétrograder {$user->name} : c'est le seul administrateur.",
                );
                return $this->redirect('/admin/users');
            }
        }

        // ── Mise à jour ───────────────────────────────────────────────────────
        $oldRole = $user->role;
        $this->userDao->promote($userId, $newRole);

        $this->dispatcher->dispatch(new RoleChanged(
            user:      $user,
            oldRole:   $oldRole,
            newRole:   $newRole,
            changedBy: (int) $this->auth->id(),
        ));

        $roleLabel = ucfirst($newRole);
        $this->session->flash(
            'success',
            "Rôle de {$user->name} mis à jour : {$roleLabel}.",
        );

        return $this->redirect('/admin/users');
    }
}
