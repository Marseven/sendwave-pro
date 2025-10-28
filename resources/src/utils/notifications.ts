import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-start',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})

export const showSuccess = (message: string) => {
  Toast.fire({
    icon: 'success',
    title: message
  })
}

export const showError = (message: string) => {
  Toast.fire({
    icon: 'error',
    title: message
  })
}

export const showWarning = (message: string) => {
  Toast.fire({
    icon: 'warning',
    title: message
  })
}

export const showInfo = (message: string) => {
  Toast.fire({
    icon: 'info',
    title: message
  })
}

export const showConfirm = async (title: string, text: string = ''): Promise<boolean> => {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Oui, confirmer',
    cancelButtonText: 'Annuler',
    position: 'center'
  })

  return result.isConfirmed
}
