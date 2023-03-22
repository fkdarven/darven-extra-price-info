function updateDropdowns(selectedDropdown) {
    // Get all dropdowns
    const dropdowns = document.getElementsByTagName("select");

    // Loop through all dropdowns
    for (let i = 0; i < dropdowns.length; i++) {
        const dropdown = dropdowns[i];

        // Skip the selected dropdown
        if (dropdown === selectedDropdown) {
            continue;
        }

        // Enable all options
        for (let j = 0; j < dropdown.options.length; j++) {
           // dropdown.options[j].disabled = false;
        }

        // Disable the selected option in other dropdowns
        const selectedOption = selectedDropdown.value;
        if (selectedOption) {
            const option = dropdown.querySelector(`option[value='${selectedOption}']`);
            if (option) {
                option.disabled = true;
            }
        }

        // Re-enable the previously selected option in this dropdown
        const previousSelectedOption = dropdown.getAttribute("data-previous-value");
        if (previousSelectedOption && previousSelectedOption !== selectedOption) {
            const option = dropdown.querySelector(`option[value='${previousSelectedOption}']`);
            if (option) {
                option.disabled = false;
            }
        }
    }

    // Store the selected value as the previous value for the selected dropdown
    selectedDropdown.setAttribute("data-previous-value", selectedDropdown.value);
}