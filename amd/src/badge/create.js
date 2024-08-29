define([
        'jquery',
        'core/config',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/fragment',
        'core/ajax',
        'local_superbadges/sweetalert',
        'core/yui'],
    function($, Config, Str, ModalFactory, ModalEvents, Fragment, Ajax, Swal, Y) {

        var Create = function(selector, contextid, course) {
            this.contextid = contextid;

            this.course = course;

            this.init(selector);
        };

        /**
         * @var {Modal} modal
         * @private
         */
        Create.prototype.modal = null;

        /**
         * @var {int} contextid
         * @private
         */
        Create.prototype.contextid = -1;

        /**
         * @var {int} course
         * @private
         */
        Create.prototype.course = -1;

        Create.prototype.init = function(selector) {
            var triggers = $(selector);

            return Str.get_string('createbadge', 'local_superbadges').then(function(title) {
                // Create the modal.
                return ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: title,
                    body: this.getBody({course: this.course}),
                    large: true
                }, triggers);
            }.bind(this)).then(function(modal) {
                // Keep a reference to the modal.
                this.modal = modal;

                // We want to reset the form every time it is opened.
                this.modal.getRoot().on(ModalEvents.hidden, function() {
                    this.modal.setBody(this.getBody({course: this.course}));
                }.bind(this));

                // We want to hide the submit buttons every time it is opened.
                this.modal.getRoot().on(ModalEvents.shown, function() {
                    this.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
                }.bind(this));

                // We catch the modal save event, and use it to submit the form inside the modal.
                // Triggering a form submission will give JS validation scripts a chance to check for errors.
                this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
                // We also catch the form submit event and use it to submit the form with ajax.
                this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));

                return this.modal;
            }.bind(this));
        };

        Create.prototype.getBody = function(formdata) {
            if (typeof formdata === "undefined") {
                formdata = {};
            }

            // Get the content of the modal.
            var params = {jsonformdata: JSON.stringify(formdata)};

            return Fragment.loadFragment('local_superbadges', 'badge_form', this.contextid, params);
        };

        Create.prototype.handleFormSubmissionResponse = function(data) {
            this.modal.hide();
            // We could trigger an event instead.
            Y.use('moodle-core-formchangechecker', function() {
                M.core_formchangechecker.reset_form_dirty_state();
            });

            var item = JSON.parse(data.data);

            var tableLine = $('<tr>' +
                '<th scope="row">'+item.id+'</th>' +
                '<td>'+item.name+'</td>' +
                '<td style="width: 160px; text-align: center;">' +
                '<a href="'+Config.wwwroot+'/local/superbadges/badgecriterias.php?id='+item.id+'" data-id="'+item.id+'" ' +
                    'class="btn btn-primary btn-sm"><i class="fa fa-cog"></i></a>' +
                '</a>' +
                '<a href="#" data-id="'+item.id+'" data-name="'+item.name+'"' +
                    'data-courseid="'+item.courseid+'" data-badgeid="'+item.badgeid+'"' +
                    'class="btn btn-warning btn-sm edit-superbadges-badge">' +
                '<i class="fa fa-pencil-square-o text-white"></i>' +
                '</a> ' +
                '<a href="#" data-id="'+item.id+'" class="btn btn-danger btn-sm delete-superbadges-badge">' +
                '<i class="fa fa-trash-o"></i>' +
                '</a> ' +
                '</td>' +
                '</tr>');

            tableLine
                .appendTo('.table-badges tbody')
                .hide().fadeIn('normal');

            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 8000,
                timerProgressBar: true,
                onOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });

            Toast.fire({
                icon: 'success',
                title: data.message
            });
        };

        Create.prototype.handleFormSubmissionFailure = function(data) {
            // Oh noes! Epic fail :(
            // Ah wait - this is normal. We need to re-display the form with errors!
            this.modal.setBody(this.getBody(data));
        };

        Create.prototype.submitFormAjax = function(e) {
            // We don't want to do a real form submission.
            e.preventDefault();

            var changeEvent = document.createEvent('HTMLEvents');
            changeEvent.initEvent('change', true, true);

            // Prompt all inputs to run their validation functions.
            // Normally this would happen when the form is submitted, but
            // since we aren't submitting the form normally we need to run client side
            // validation.
            this.modal.getRoot().find(':input').each(function(index, element) {
                element.dispatchEvent(changeEvent);
            });

            // Now the change events have run, see if there are any "invalid" form fields.
            var invalid = $.merge(
                this.modal.getRoot().find('[aria-invalid="true"]'),
                this.modal.getRoot().find('.error')
            );

            // If we found invalid fields, focus on the first one and do not submit via ajax.
            if (invalid.length) {
                invalid.first().focus();
                return;
            }

            // Convert all the form elements values to a serialised string.
            var formData = this.modal.getRoot().find('form').serialize();

            // Now we can continue...
            Ajax.call([{
                methodname: 'local_superbadges_createbadge',
                args: {contextid: this.contextid, course: this.course, jsonformdata: JSON.stringify(formData)},
                done: this.handleFormSubmissionResponse.bind(this),
                fail: this.handleFormSubmissionFailure.bind(this, formData)
            }]);
        };

        Create.prototype.submitForm = function(e) {
            e.preventDefault();

            this.modal.getRoot().find('form').submit();
        };

        return {
            init: function(selector, contextid, course) {
                return new Create(selector, contextid, course);
            }
        };
    }
);
