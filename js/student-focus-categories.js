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
 * JS for block student_focus_categories
 *
 * @package    block_student_focus_categories
 * @copyright  2022 jan Eticeo <contact@eticeo.fr>
 * @author     2022 jan Guevara Gabrielle <gabrielle.guevara@eticeo.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
/**
 * Sort the element of the table according to a column
 * @param sortType      | string name of the col element on which we want to sort
 * @param desc          | boolean true if we want sort descendant, false if we want ascendant
 */
function studentFocusCategories_sortTable(sortType, desc, idCat) {
    var blockId = '.student-focus-categories-block .category-homepage-list.category-'+idCat;
    var countItem = $(blockId+' td.col_'+sortType).length;
    if (desc) {
        //we stack from 0 to countItem so that countItem is first
        for (var item = 0; item < countItem; item++) {
            $(blockId+' tbody').prepend($(blockId+' tr:has( td:not(.table-header).col_'+sortType+'[data-sortvalue="'+item+'"])'));
        }
        $(blockId+' th.col_'+sortType+' i').attr('onclick', "studentFocusCategories_sortTable('"+sortType+"', false, "+idCat+")");
        $(blockId+' th.col_'+sortType+' i').attr('class', 'fa fa-sort-up');
    } else {
        //we stack from countItem to 0 so that 0 is first
        for (countItem; countItem >= 0; countItem--) {
            $(blockId+'  tbody').prepend($(blockId+' tr:has( td.col_'+sortType+'[data-sortvalue="'+countItem+'"])'));
        }
        $(blockId+' th.col_'+sortType+' i').attr('onclick', "studentFocusCategories_sortTable('"+sortType+"', true, "+idCat+")");
        $(blockId+' th.col_'+sortType+' i').attr('class', 'fa fa-sort-down');
    }
    $(blockId+' th:not(.col_'+sortType+') i').attr('class', 'fa fa-sort');
}


/**
 * If the page is too small, we hide data and put a button which show these data
 * @param idCourse      | int course id we want to deploy
 */
function studentFocusCategories_deployCourse(idCourse) {
    if (!$('.block_student_focus_categories .course-line-'+idCourse+' .container-data').length) {
        var html = '<td class="container-data">';
        $('.course-line-'+idCourse+' [data-primary="false"]').each(function() {
            html += '<span class="title">'+$('th.'+$(this).attr('class')+' span').html();
            html += $(this).html()+'</span>';
        });
        html +='</td>';
        $('.block_student_focus_categories .course-line-'+idCourse+' td:nth-child(2)').after(html);
    }
    $('.block_student_focus_categories .course-line-'+idCourse+' .deployCourse').hide();
    $('.block_student_focus_categories .course-line-'+idCourse+' .container-data').show();
    $('.block_student_focus_categories .course-line-'+idCourse+' .foldCourse').show();
}

/**
 * Fold the course
 * @param idCourse    | int id of the course we want to fold
 */
function studentFocusCategories_foldCourse(idCourse) {
    $('.block_student_focus_categories .course-line-'+idCourse+' .container-data').hide();
    $('.block_student_focus_categories .course-line-'+idCourse+' .foldCourse').hide();
    $('.block_student_focus_categories .course-line-'+idCourse+' .deployCourse').show();
}

/**
 * Open the course in the cuurrent page
 * @param idCourse    | int id of the course we want to open
 */
function studentFocusCategories_openCourse(idCourse) {
    location.href = M.cfg.wwwroot+'/course/view.php?id='+idCourse;
}
