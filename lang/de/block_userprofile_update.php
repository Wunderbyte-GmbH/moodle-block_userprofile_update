<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * De language file for the plugin.
 *
 * @package    block_userprofile_update
 * @subpackage userprofile_update
 * @author     David Bogner
 * @copyright  2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['canmanageusers'] = 'Kann Mitarbeiter*innen verwalten und anlegen';
$string['emailpostfix'] = 'E-Mail-Postfix';
$string['emailpostfix_desc'] = 'Die Domain oder das Postfix, das an Benutzernamen für die automatische E-Mail-Generierung angehängt wird (z.B. @example.com)';
$string['eventuserprofile_updated'] = 'Nutzerprofil wurde geändert';
$string['ispartner'] = 'Ist Partner';
$string['ispartner_desc'] = 'Wählen Sie ein benutzerdefiniertes Benutzerprofilfeld aus, um festzustellen, ob der Benutzer ein Partner ist.';
$string['partnerid'] = 'Partner-ID';
$string['partnerid_desc'] = 'Wählen Sie ein benutzerdefiniertes Benutzerprofilfeld aus, in dem die Partner-IDs definiert sind.';
$string['partnerstatus'] = 'Partner-Status';
$string['partnerstatus_desc'] = 'Wählen Sie das Feld für das Programm an dem der Partner teilnimmt';
$string['pluginname'] = 'Nutzerprofiländerung';
$string['selecttenant'] = 'Benutzerprofilfeld für Mandant auswählen';
$string['selecttenant_desc'] = 'Wählen Sie ein Benutzerprofilfeld aus,
 um es mit einem Mandanten zu verknüpfen. Sie müssen es in /user/profile/index.php erstellen, bevor Sie es hier auswählen können.
 Es sollte eine Dropdown-Liste mit dem Namen aller verfügbaren Mandanten sein';
$string['showonlygroupmembers'] = 'Nur Nutzer/innen der zugehörigen Gruppe/n anzeigen';
$string['showonlygroupmembersdesc'] = 'Die Änderungen von Nutzerprofilen nur zulassen, wenn der User mit den entsprechenden Rechten auch Mitglied der Gruppe der zu ändernden User ist';
$string['showonlymatchingusers'] = 'Zeige nur Benutzer, die denselben Mandantennamen im Mandantenprofilfeld des aktuellen Benutzers haben.';
$string['showonlymatchingusersdesc'] = 'Erlauben Sie die Bearbeitung von Benutzern nur für Benutzer, die denselben Mandantennamen
 in dem Profilfeld haben, das Sie als Mieterprofilfeld ausgewählt haben.';
$string['title'] = 'Nutzerprofiländerung';

$string['useautoemail'] = 'Automatische E-Mail-Generierung verwenden';
$string['useautoemail_desc'] = 'Aktivieren Sie die automatische E-Mail-Adressgenerierung basierend auf Benutzername und E-Mail-Postfix';
$string['usermanager'] = 'Mitarbeiter*innenverwaltung';
$string['userprofile_update:addinstance'] = 'Block Nutzerprofiländerung hinzufügen';
$string['userprofile_update:createuser'] = 'Neue Nutzer/in anlegen';
$string['userprofile_update:suspenduser'] = 'Nutzerkonto sperren';
$string['userprofile_update:updateuserprofile'] = 'Nutzerprofildaten bearbeiten';
