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
 * Bulk certificate actions for the simplecertificate report.
 *
 * @module     mod_simplecertificate/bulk_certificate_actions
 * @copyright  2026 David Herney - BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as reportSelectors from 'core_reportbuilder/local/selectors';
import * as tableEvents from 'core_table/local/dynamic/events';
import DeleteCancelModal from 'core/modal_delete_cancel';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

const Selectors = {
    wrapper: '[data-region="bulk-certificates-wrapper"]',
    checkbox: 'input[type="checkbox"][data-togglegroup="report-select-all"][data-toggle="slave"]',
    masterCheckbox: 'input[type="checkbox"][data-togglegroup="report-select-all"][data-toggle="master"]',
    checkedRows: '[data-togglegroup="report-select-all"][data-toggle="slave"]:checked',
    submitBtn: '#bulk-submit-btn',
    typeSelect: '#bulk-type-select',
};

/**
 * Initialise module.
 */
export const init = () => {

    const wrapper = document.querySelector(Selectors.wrapper);
    if (!wrapper) {
        return;
    }

    const report = wrapper.querySelector(reportSelectors.regions.report);
    const submitBtn = wrapper.querySelector(Selectors.submitBtn);
    const typeSelect = wrapper.querySelector(Selectors.typeSelect);
    if (!report || !submitBtn) {
        return;
    }

    let selectedUserIds = [];

    const requiresSelection = () => {
        const opt = typeSelect.options[typeSelect.selectedIndex];
        return !opt.hasAttribute('data-no-selection');
    };

    const updateState = () => {
        const checkedBoxes = [...report.querySelectorAll(Selectors.checkedRows)];
        selectedUserIds = checkedBoxes.map(check => parseInt(check.value));
        submitBtn.disabled = requiresSelection() && checkedBoxes.length === 0;
    };

    updateState();

    document.addEventListener('change', event => {
        if ((event.target.matches(Selectors.checkbox) || event.target.matches(Selectors.masterCheckbox))
                && report.contains(event.target)) {
            updateState();
        }
    });

    document.addEventListener(tableEvents.tableContentRefreshed, event => {
        if (report.contains(event.target)) {
            updateState();
        }
    });

    typeSelect.addEventListener('change', () => {
        updateState();
    });

    const submitForm = (action, type) => {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = wrapper.dataset.actionurl;
        form.style.display = 'none';

        const fields = {
            id: wrapper.dataset.cmid,
            tab: wrapper.dataset.tab,
            action: action,
            type: type,
            sesskey: wrapper.dataset.sesskey,
            userids: selectedUserIds.join(','),
        };

        for (const [name, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    };

    submitBtn.addEventListener('click', async() => {
        if (requiresSelection() && selectedUserIds.length === 0) {
            return;
        }

        const selectedOption = typeSelect.options[typeSelect.selectedIndex];
        const action = selectedOption.dataset.action || 'download';
        const type = selectedOption.dataset.type || '';

        if (action === 'delete') {
            const modal = await DeleteCancelModal.create({
                title: await getString('deletissuedcertificates', 'simplecertificate'),
                body: await getString('deleteconfirm', 'simplecertificate'),
                show: true,
                removeOnClose: true,
            });

            modal.getRoot().on(ModalEvents.delete, () => {
                submitForm(action, type);
            });
            return;
        }

        submitForm(action, type);
    });
};
