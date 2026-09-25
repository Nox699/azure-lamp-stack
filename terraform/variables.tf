variable "project_name" {
  description = "Prefix used for Azure resource names."
  type        = string
  default     = "lamp-school"
}

variable "location" {
  description = "Azure region."
  type        = string
  default     = "Sweden Central"
}

variable "vm_size" {
  description = "Azure VM size. B2als_v2 is the selected Sweden Central size for Docker + Apache/PHP + MariaDB."
  type        = string
  default     = "Standard_B2als_v2"
}

variable "admin_username" {
  description = "Linux administrator user."
  type        = string
  default     = "azureadmin"
}

variable "ssh_public_key_path" {
  description = "Path to the local SSH public key used for VM login."
  type        = string
  default     = "~/.ssh/azure-lamp.pub"
}

variable "admin_cidr" {
  description = "Public administrator IP/CIDR allowed to SSH, for example 203.0.113.10/32. Do not use 0.0.0.0/0 unless temporarily required."
  type        = string

  validation {
    condition     = can(cidrnetmask(var.admin_cidr))
    error_message = "admin_cidr must be a valid IPv4/IPv6 CIDR such as 203.0.113.10/32."
  }
}
