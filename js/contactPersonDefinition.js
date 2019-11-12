cj(document).ready(
    function ()
    {
        /**
         * The defined option value representing the organisation.
         */
        const organisationOptionValue = 0;

        const contactPersonSelectionElement = cj('select.contact-person-selection');

        /**
         * Set all contacts with optional organisations.
         */
        function setAll (includeOrganisations)
        {
            contactPersonSelectionElement.each(
                function ()
                {
                    const optionValues = [];

                    cj(this).find('option').each(
                        function ()
                        {
                            const optionValue = cj(this).val();

                            if (includeOrganisations || (optionValue != organisationOptionValue)) // No strict check! We have type differences here.
                            {
                                optionValues.push(optionValue);
                            }
                        }
                    );

                    cj(this).val(optionValues);
                }
            );

            // We must trigger the change for the select2 field to update visually:
            contactPersonSelectionElement.trigger('change');
        }

        /**
         * Only set organisations.
         */
        function setOrganisations ()
        {
            contactPersonSelectionElement.each(
                function ()
                {
                    cj(this).val([organisationOptionValue]);
                }
            );

            // We must trigger the change fort the select2 field to update visually:
            contactPersonSelectionElement.trigger('change');
        }

        // Make the three buttons to their jobs:
        cj('#contact_person_quick_setting_all').click(() => setAll(true));
        cj('#contact_person_quick_setting_contact_persons_only').click(() => setAll(false));
        cj('#contact_person_quick_setting_organisations_only').click(setOrganisations);
    }
);
