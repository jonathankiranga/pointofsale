 <style>
.step {
  display: none;
}

.step.active {
  display: block;
}</style>
    
    <div id="wizard">
  <div class="step active">
    <h2>Step 1: Upload Excel File</h2>
    <form id="formStep1">
      <input type="file" id="fileInput" required>
      <button type="submit">Next</button>
    </form>
  </div>
  <div class="step">
    <h2>Step 2: Import Data</h2>
    <form id="formStep2">
      <button type="submit">Next</button>
    </form>
  </div>
  <div class="step">
    <h2>Step 3: Save Data</h2>
    <form id="formStep3">
      <button type="submit">Save</button>
    </form>
  </div>
</div>
<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>

    <script type="text/javascript">
       const steps = document.querySelectorAll('.step');
let currentStep = 0;
let fileData = null;
let importedData = null;

function handleFormSubmit(event) {
  event.preventDefault();
  const currentForm = event.target;
  
  if (currentStep === 0) {
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
      fileData = e.target.result;
      steps[currentStep].classList.remove('active');
      currentStep++;
      steps[currentStep].classList.add('active');
    };
    
    reader.readAsText(file);
  } else if (currentStep === 1) {
    // Parse the imported data from the Excel file
    importedData = parseExcelData(fileData);
    
    steps[currentStep].classList.remove('active');
    currentStep++;
    steps[currentStep].classList.add('active');
  } else if (currentStep === 2) {
    // Save the imported data
    saveData(importedData);
    
    // Wizard completed, you can perform final actions here
    console.log('Wizard completed!');
    
    // Reset the wizard
    currentStep = 0;
    fileData = null;
    importedData = null;
    steps[currentStep].classList.add('active');
  }
}

function parseExcelData(fileData) {
  // Implement your logic to parse the Excel data here
  // For demonstration purposes, we'll assume the data is already parsed
  return [
    { name: 'John', age: 25 },
    { name: 'Jane', age: 30 },
    { name: 'Bob', age: 35 }
  ];
}

function saveData(data) {
  // Implement your logic to save the data here
  console.log('Data saved:', data);
}

// Add form submit event listeners to each form
const forms = document.querySelectorAll('form');
forms.forEach((form) => {
  form.addEventListener('submit', handleFormSubmit);
});

// Hide all steps except the first one initially
for (let i = 1; i < steps.length; i++) {
  steps[i].classList.remove('active');
}
    </script>
