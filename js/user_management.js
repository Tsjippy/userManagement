async function disableUserAccount(target){
	var response	= await FormSubmit.submitForm(target, 'user_management/disable-user-account');

	if(response){
		if(target.textContent.includes('Disable')){
			target.textContent	= target.textContent.replace('Disable', 'Enable');
		}else{
			target.textContent	= target.textContent.replace('Enable', 'Disable');
		}
		Main.displayMessage(response);
	}
}

async function updateUserRoles(target){
    var response	= await FormSubmit.submitForm(target, 'user_management/update_roles');

	if(response){
		Main.displayMessage(response);
	}
}

async function extendValidity(target){
	var response	= await FormSubmit.submitForm(target, 'user_management/extend_validity');

	if(response){
		Main.displayMessage(response);
	}
}

async function createUserAccount(target){
    var response	= await FormSubmit.submitForm(target, 'user_management/add_useraccount');

	if(response){
		Main.displayMessage(response.message);
	}
}

document.addEventListener('click', ev=>{
    const target    = ev.target;

    if(target.name == "disable-user-account"){	
		ev.preventDefault();
        disableUserAccount(target);
    }else if(target.name == 'updateroles'){
		ev.preventDefault();
        updateUserRoles(target);
    }else if(target.name == 'extend_validity'){
		ev.preventDefault();
        extendValidity(target);
    }else if(target.name == 'adduseraccount'){
		ev.preventDefault();
		createUserAccount(target);
	}else{
		return;
	}

	ev.stopImmediatePropagation();
});

console.log('user management js loaded');