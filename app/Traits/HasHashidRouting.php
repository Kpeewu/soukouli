<?php

namespace App\Traits;

use Hashids\Hashids;

/**
 * Permet à un modèle d'utiliser un identifiant obfusqué (hashid) dans les URLs
 * à la place de sa clé primaire numérique, sans ajouter de colonne en base.
 *
 * Le hashid est calculé à la volée à partir de l'id auto-incrémenté existant ;
 * aucune migration n'est nécessaire. La résolution (route model binding) décode
 * le hashid pour retrouver l'enregistrement correspondant, et retourne une 404
 * si le hashid est invalide ou ne correspond à aucun enregistrement.
 *
 * Le salt de base (config('hashids.connections.main.salt')) est combiné avec
 * le nom de classe du modèle: sans ca, deux modeles differents partageant le
 * meme id numerique (ex: Eleve #1 et Classe #1) produiraient un hashid
 * identique et interchangeable d'un type de ressource a l'autre.
 */
trait HasHashidRouting
{
    /**
     * Instance Hashids dediee a ce modele (salt derive par classe).
     *
     * Hashids ne lit qu'une fenetre limitee du salt (~longueur de l'alphabet,
     * soit ~61 caracteres). Une simple concatenation salt+classe ferait donc
     * ignorer le suffixe si le salt de base est deja long: on passe par un
     * sha256 pour que la classe du modele influence tout le salt effectif.
     */
    protected function hashids(): Hashids
    {
        $config = config('hashids.connections.main');
        $salt = hash('sha256', $config['salt'] . '|' . static::class);

        return new Hashids($salt, $config['length'] ?? 8);
    }

    /**
     * Nom de la colonne utilisée comme clé de route (utilisé en interne par
     * Laravel ; ne correspond pas à une vraie colonne, cf. resolveRouteBinding).
     */
    public function getRouteKeyName(): string
    {
        return 'hashid';
    }

    /**
     * Retourne la valeur de la clé de route: le hashid encodé, pas l'id brut.
     * Appelé automatiquement par Laravel quand une instance de modèle est
     * passée à route()/redirect()->route() ou à un lien Eloquent implicite.
     */
    public function getRouteKey(): string
    {
        return $this->hashids()->encode($this->getKey());
    }

    /**
     * Résout le binding de route implicite: décode le hashid présent dans
     * l'URL et retourne le modèle correspondant, ou 404 si invalide/absent.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $ids = $this->hashids()->decode($value);

        if (empty($ids)) {
            abort(404);
        }

        return $this->where($this->getKeyName(), $ids[0])->firstOrFail();
    }

    /**
     * Accesseur pratique: $model->hashid, pour les cas où l'on doit
     * construire une URL en dehors du helper route() (JS/AJAX, attributs
     * data-*, chaînes construites à la main dans les vues).
     */
    public function getHashidAttribute(): string
    {
        return $this->getRouteKey();
    }
}
