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
 
 
 /**
 * Ajoute une categorie a la fin de la liste, la rend disable dans le select puis appelle la fonction qui recalcule l'ordre
 * @param idCategory    | int optionnel id de la categorie que l'on souhaite bouger
 */
function studentFocusCategories_addCategory(idCategory) {
    if (!idCategory) {
        var idCategory = $('.category-select-list option:selected').val();
    }

    if (!idCategory || $('#eticeo-categories-list .categories-option[data-categoryid="'+idCategory +'"]').length > 0 || $('input[name="config_eticeo_categories['+idCategory+']"]').attr('name') == undefined) {
        return null;
    }

    var card = '<div class="categories-option" data-categoryid="'+idCategory +'">'+$('.category-select-list option[value="'+idCategory +'"]').html();
    card += '<i class="fa fa-trash" onclick="studentFocusCategories_deleteCategory('+idCategory +')"></i><br/></div>';
    $('#eticeo-categories-list').append(card);

    studentFocusCategories_makeCohortSelectAction(idCategory);

    studentFocusCategories_calculateCategoriesOrder();
}

function studentFocusCategories_makeCohortSelectAction(idCategory) {

    var cohortsSector = $('#fitem_id_config_cohorts_enabled .form-inline').html();
    var cohortSelect = '<span class="cohort-selection" id="cohort-selection-'+idCategory+'">'+cohortsSector.replaceAll('cohorts_enabled', 'cohorts_enabled_'+idCategory)+'</span>';
    $('.categories-option[data-categoryid="'+idCategory +'"]').append(cohortSelect);

    var selectId = '[name="config_cohorts_enabled_'+idCategory+'[]"]';
    $(selectId).chosen({placeholder: "Select cohorts"}).change(function (e) {
        var cohortIds = $(this).val().join(',');
        studentFocusCategories_updateCategoryParam('input[name="config_eticeo_categories['+idCategory+']"]', 'cohorts', cohortIds, 'replace');
    });
    var params = studentFocusCategories_getCategoryParams('input[name="config_eticeo_categories['+idCategory+']"]');
    if (params['cohorts'] != undefined) {
        params = params['cohorts'].split(',');
        $(selectId).val(params).trigger("chosen:updated");
    }
}


function studentFocusCategories_makeCohortSelect(idCategory) {
    $('.categories-option [data-categoryid="'+idCategory +']').append($('#cohort-selection-'+idCategory));
    $('#cohort-selection-'+idCategory).removeAttr('hidden');
}

/**
 * Supprime une categorie de la liste
 * @param idCategory    | int id de la categorie qu'on souhaite supprimer
 */
function studentFocusCategories_deleteCategory(idCategory) {
    studentFocusCategories_updateCategoryParam('input[name="config_eticeo_categories['+idCategory+']"]', 'cohorts', '', 'replace');
    //('input[name="config_eticeo_categories['+idCategory+']"]', 'order', index);
    $('#eticeo-categories-list [data-categoryid="'+idCategory +'"]').remove();
    studentFocusCategories_calculateCategoriesOrder();
}

/**
 * Retourne les parametres d'une categorie sous forme de tableau
 * @param object
 * @returns {*|jQuery}
 */
function studentFocusCategories_getCategoryParams(object) {
    var params = $(object).val();
    var isArray = params.split('{');
    if (isArray[1] == undefined) {
        params = {'order' : params};
    } else {
        params = JSON.parse(params);
    }

    return params;
}

/**
 * Modifier une parametre d'une categorie et l'enregistre
 * @param object        | string id de l'object ou object lui même
 * @param paramName     | string nom du parametre a update
 * @param paramValue    | string nom du nouveau paramètre
 * @param action        | string action a realiser (add, remove, replace)
 */
function studentFocusCategories_updateCategoryParam(object, paramName, paramValue, action) {
    if (!action) {
        action = 'replace';
    }
    var params = studentFocusCategories_getCategoryParams(object) ;
    if (action == 'add' && params[paramName] != undefined) {
        paramValue = params[paramName]+','+paramValue;
    } else if (action == 'remove') {
        var allParamsValues = params[paramName].split(',');
        for (id in allParamsValues) {
            if (allParamsValues[id] == paramValue) {
                delete allParamsValues[id];
            }
        }
        paramValue = allParamsValues.join(',');
    }
    if (paramValue == '' ) {
        delete params[paramName];
    } else {
        params[paramName] = paramValue;
    }
    params = JSON.stringify(params);
    $(object).val(params);
}

/**
 * recalcule l'ordre de toutes les categories
 */
function studentFocusCategories_calculateCategoriesOrder() {
    $('input[name^="config_eticeo_categories["]').each(function(index) {
        studentFocusCategories_updateCategoryParam(this, 'order', 0);
    })
    $('#eticeo-categories-list .categories-option').each(function(index) {
        index = index+1;
        var idCategory = $('#eticeo-categories-list .categories-option:nth-child('+index+')').data('categoryid');

        studentFocusCategories_updateCategoryParam('input[name="config_eticeo_categories['+idCategory+']"]', 'order', index);
    });
}

/**
 * Met les categories precedement enregistrees au bon endroit
 */
function studentFocusCategories_initCategories() {
    var array = [];
    $('input[name^="config_eticeo_categories"]').each(function(index) {
        index = index;
        var idCategory = $(this).attr('name');
        if (idCategory != undefined) {

            idCategory = idCategory.replace('config_eticeo_categories[', '');
            idCategory = idCategory.replace(']', '');
            var params = studentFocusCategories_getCategoryParams(this);
            var order = params['order'];
            /*var order = $(this).val();*/
            if (order > 0) {
                array[order-1] = idCategory;
            }
            studentFocusCategories_updateCategoryParam(this, 'order', 0);
        }
    });
    array.forEach(element => studentFocusCategories_addCategory(element));
}

$(function() {
    $('.category-select-list>div:last-child').append('<div id="eticeo-categories-list"></div>');
    studentFocusCategories_initCategories();
    $('#eticeo-categories-list').sortable({stop: function( event, ui ) { studentFocusCategories_calculateCategoriesOrder(); }});

    $('.category-select-list .custom-select').chosen({placeholder: "Select a category"});

    $('[name="config_user_role[]"]').chosen({placeholder: "Select a user role"});
});

