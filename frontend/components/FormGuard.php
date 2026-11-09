<?php

declare(strict_types=1);

namespace frontend\components;

use Yii;

/**
 * Stops most form bots without bothering people: a field only bots fill in, and a minimum time between
 * showing a form and sending it. Use shown() when rendering, field() inside the form, isBot() on POST.
 * A person can trip it too (an extension filling every field, a very fast paste), so the contact form keeps
 * what it catches as Spam rather than throwing it away.
 */
final class FormGuard
{
    /** A name browsers and password managers have no reason to autofill. */
    private const TRAP = 'mw_hp_extra';

    public static function shown(string $form): void
    {
        Yii::$app->session->set('form-shown:' . $form, time());
    }

    public static function field(): string
    {
        return '<div class="mw-hp" aria-hidden="true"><label>Leave this field empty '
            . '<input type="text" name="' . self::TRAP . '" value="" tabindex="-1" autocomplete="off"></label></div>';
    }

    public static function isBot(string $form): bool
    {
        $trap = Yii::$app->request->post(self::TRAP);
        if (is_array($trap) || (is_string($trap) && $trap !== '')) {
            return true;
        }
        $shownAt = (int) Yii::$app->session->get('form-shown:' . $form, 0);

        return $shownAt > 0 && time() - $shownAt < (int) (Yii::$app->params['formMinSeconds'] ?? 3);
    }
}
