output "public_ip" {
  description = "Public IPv4 address of the LAMP VM."
  value       = azurerm_public_ip.main.ip_address
}

output "ssh_command" {
  description = "Example SSH command."
  value       = "ssh -i ${trimsuffix(var.ssh_public_key_path, ".pub")} ${var.admin_username}@${azurerm_public_ip.main.ip_address}"
}

output "web_url" {
  description = "HTTP URL for the deployed application."
  value       = "http://${azurerm_public_ip.main.ip_address}"
}
