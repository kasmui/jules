<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Theme Settings</h3>
    </div>
    <div class="card-body">
        <p>Select a color scheme for the application. Your choice is saved automatically.</p>
        <div class="d-flex flex-wrap gap-3">
            <div class="theme-color" data-theme="default" onclick="changeTheme('default', this)" style="background-color: #2c3e50; color: white;">Default</div>
            <div class="theme-color" data-theme="blue" onclick="changeTheme('blue', this)" style="background-color: #3498db; color: white;">Blue</div>
            <div class="theme-color" data-theme="green" onclick="changeTheme('green', this)" style="background-color: #27ae60; color: white;">Green</div>
            <div class="theme-color" data-theme="red" onclick="changeTheme('red', this)" style="background-color: #e74c3c; color: white;">Red</div>
            <div class="theme-color" data-theme="purple" onclick="changeTheme('purple', this)" style="background-color: #9b59b6; color: white;">Purple</div>
            <div class="theme-color" data-theme="orange" onclick="changeTheme('orange', this)" style="background-color: #f39c12; color: white;">Orange</div>
            <div class="theme-color" data-theme="dark" onclick="changeTheme('dark', this)" style="background-color: #34495e; color: white;">Dark</div>
            <div class="theme-color" data-theme="teal" onclick="changeTheme('teal', this)" style="background-color: #1abc9c; color: white;">Teal</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Font Settings</h3>
    </div>
    <div class="card-body">
        <p>Choose the primary font for the application. Your choice is saved automatically.</p>
        <div class="d-flex flex-wrap gap-3">
            <div class="font-option" data-font="poppins" onclick="changeFont('poppins', this)" style="font-family: 'Poppins', sans-serif;">Poppins</div>
            <div class="font-option" data-font="amiri" onclick="changeFont('amiri', this)" style="font-family: 'Amiri', serif;">Amiri</div>
            <div class="font-option" data-font="arial" onclick="changeFont('arial', this)" style="font-family: Arial, sans-serif;">Arial</div>
            <div class="font-option" data-font="verdana" onclick="changeFont('verdana', this)" style="font-family: Verdana, sans-serif;">Verdana</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Layout Settings</h3>
    </div>
    <div class="card-body">
        <p>Adjust the main content area width. Your choice is saved automatically.</p>
        <div class="d-flex flex-wrap gap-3">
            <div class="width-option" data-width="90%" onclick="changePageWidth('90%', this)">90%</div>
            <div class="width-option" data-width="96%" onclick="changePageWidth('96%', this)">96% (Default)</div>
            <div class="width-option" data-width="100%" onclick="changePageWidth('100%', this)">100%</div>
        </div>
    </div>
</div>
