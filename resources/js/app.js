/**
 * Build: @vite-build-ts
 */
import './bootstrap';

window.__BUILD_ID__ = '__BUILD_ID__';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { crudModal } from './alpine/crud-modal';
import { lessonOfferModal } from './alpine/lesson-offer-modal';
import { documentModal } from './alpine/document-modal';
import { presensiModal } from './alpine/presensi-modal';
import { EnrollmentForm } from './alpine/enrollment-form';
import { forceDeleteActions } from './alpine/force-delete';
import { bulkHibernateActions } from './alpine/bulk-hibernate';
import { studentsInactiveModal } from './alpine/students-inactive-modal';
import { enrollmentsInactiveModal } from './alpine/enrollments-inactive-modal';
import { fdOnlyInactiveModal } from './alpine/fd-only-inactive-modal';
import { teachersInactiveModal } from './alpine/teachers-inactive-modal';
import { parentsInactiveModal } from './alpine/parents-inactive-modal';
import { forceDeleteModal } from './alpine/force-delete-modal';
import { discountModal } from './alpine/discount-modal';
import { newStudentModal } from './alpine/new-student-modal';
import { teacherRegistrantModal } from './alpine/teacher-registrant-modal';
import { attendanceValidationModal } from './alpine/attendance-validation-modal';
import { attendanceDetailModal } from './alpine/attendance-detail-modal';
import { classAttendanceModal } from './alpine/class-attendance-modal';
import { classSessionModal } from './alpine/class-session-modal';
import { classPresensiModal } from './alpine/class-presensi-modal';

import './utils/toast';
import './utils/ajax';

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.data('crudModal', crudModal);
Alpine.data('lessonOfferModal', lessonOfferModal);
Alpine.data('documentModal', documentModal);
Alpine.data('presensiModal', presensiModal);
Alpine.data('forceDeleteActions', forceDeleteActions);
Alpine.data('discountModal', discountModal);
Alpine.data('newStudentModal', newStudentModal);
Alpine.data('teacherRegistrantModal', teacherRegistrantModal);
Alpine.data('attendanceValidationModal', attendanceValidationModal);
Alpine.data('attendanceDetailModal', attendanceDetailModal);
Alpine.data('classAttendanceModal', classAttendanceModal);
Alpine.data('classSessionModal', classSessionModal);
Alpine.data('classPresensiModal', classPresensiModal);
Alpine.data('bulkHibernateActions', bulkHibernateActions);
// Expose to window so per-page Alpine.data factories (e.g. enrollmentModal)
// can spread it via `...crudModal({...})` from their inline <script> scope.
// Alpine.data() only registers the provider in Alpine's internal scope — it
// does NOT make the identifier accessible in the lexical scope of an
// Alpine.data factory defined in a Blade <script>.
window.crudModal = crudModal;
window.lessonOfferModal = lessonOfferModal;
window.documentModal = documentModal;
window.presensiModal = presensiModal;
window.EnrollmentForm = EnrollmentForm;
window.forceDeleteActions = forceDeleteActions;
window.bulkHibernateActions = bulkHibernateActions;
window.studentsInactiveModal = studentsInactiveModal;
window.enrollmentsInactiveModal = enrollmentsInactiveModal;
window.fdOnlyInactiveModal = fdOnlyInactiveModal;
window.teachersInactiveModal = teachersInactiveModal;
window.parentsInactiveModal = parentsInactiveModal;
window.forceDeleteModal = forceDeleteModal;
window.discountModal = discountModal;
window.newStudentModal = newStudentModal;
window.teacherRegistrantModal = teacherRegistrantModal;
window.attendanceValidationModal = attendanceValidationModal;
window.attendanceDetailModal = attendanceDetailModal;
window.classAttendanceModal = classAttendanceModal;
window.classSessionModal = classSessionModal;
window.classPresensiModal = classPresensiModal;

Alpine.start();

// ── PWA Service Worker Registration ──────────────────────────────────────────
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('[SW] Registered:', registration.scope);

                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[SW] New version available. Refresh to update.');
                            }
                        });
                    }
                });
            })
            .catch((err) => {
                console.error('[SW] Registration failed:', err);
            });
    });
}
