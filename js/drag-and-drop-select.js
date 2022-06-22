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
 * JS for edit-form.php of block student_focus_categories
 *
 * @package    block_student_focus_categories
 * @copyright  2022 jan Eticeo <contact@eticeo.fr>
 * @author     2022 jan Guevara Gabrielle <gabrielle.guevara@eticeo.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$(function() {

    $('.drag-and-drop-select-list').each(function(index) {
        var configName = $(this).attr('class');

        configName = configName.split('dd-');
        configName = configName[1].split(' ');
        configName = configName[0];

        $('.dd-'+configName+'>div:last-child').append('<div id="eticeo-dd-options-list-'+configName+'"></div>');
        studentFocusCategories_initOptions(configName);
        $('#eticeo-dd-options-list-'+configName).sortable({stop: function( event, ui ) { studentFocusCategories_calculateOptionsOrder(configName); }});
        $(this).find('.custom-select').attr('id', 'select2-'+configName)
        $('#select2-'+configName).chosen({placeholder: "Select a option"});
        $('#select2-'+configName).val(null).trigger('change');
    });

});

/**
 * Ajoute une option à la fin de la liste, la rend disable dans le select puis appelle la fonction qui recalcule l'ordre
 * @param idOption    | int optionnel id de l'option que l'on souhaite bouger
 */
function studentFocusCategories_addOption(configName, idOption = null) {
    if (!idOption) {
        var idOption = $('.dd-'+configName+' option:selected').val();
    }
    var optionName = 'input[name="'+configName+'['+idOption+']"]';
    if (!idOption || $(optionName).attr('name') == undefined) {
        return null;
    }
    var card = '<div class="options-option" data-optionid="'+idOption +'">'+$('.dd-'+configName+' option[value="'+idOption +'"]').html();
    card += '<i class="fa fa-trash" onclick="studentFocusCategories_deleteOption(\''+idOption +'\', \''+configName +'\')"></i></div>';
    $('#eticeo-dd-options-list-'+configName).append(card);
    studentFocusCategories_calculateOptionsOrder(configName);
    $('.dd-'+configName+' .custom-select option[value="'+idOption +'"]').attr('disabled', 'disabled');
}

/**
 * Supprime une option de la liste
 * @param idOption    | int id de la option qu'on souhaite supprimer
 */
function studentFocusCategories_deleteOption(idOption, configName) {
    $('#eticeo-dd-options-list-'+configName+' [data-optionid="'+idOption +'"]').remove();
    $('.dd-'+configName+' .form-autocomplete-suggestions span[data-value="'+idOption +'"]').removeAttr('hidden');
    studentFocusCategories_calculateOptionsOrder(configName);
    $('.dd-'+configName+' .custom-select option[value="'+idOption +'"]').removeAttr('disabled');
}

/**
 * recalcule l'ordre de toutes les options
 */
function studentFocusCategories_calculateOptionsOrder(configName) {
    $('input[name^="'+configName+'["]').val(0);

    $('#eticeo-dd-options-list-'+configName+' .options-option').each(function(index) {
        index = index+1;
        var idOption = $('#eticeo-dd-options-list-'+configName+' .options-option:nth-child('+index+')').data('optionid');
        $('input[name="'+configName+'['+idOption+']"]').val(index);
    });
}

/**
 * Met les options précédement enregistrées au bon endroit
 */
function studentFocusCategories_initOptions(configName) {
    var array = [];
    $('input[name^="'+configName+'["]').each(function(index) {
        var idOption = $(this).attr('name');
        if (idOption != undefined) {
            idOption = idOption.replace(configName+"[", '');
            idOption = idOption.replace("]", '');
            var order = $(this).val();
            if (order > 0) {
                array[order-1] = idOption;
            }
        }
    });
    array.forEach(element => studentFocusCategories_addOption(configName, element));
}

