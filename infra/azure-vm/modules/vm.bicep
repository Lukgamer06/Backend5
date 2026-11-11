param adminUsername string

param environmentName string

param location string

@secure()
param sshPublicKey string

param sshSourceAddressPrefix string

param tags object

var resourceToken = uniqueString(subscription().id, resourceGroup().id)
var vmName = 'vm-hostalconnect-${resourceToken}'
var vnetName = 'vnet-hostalconnect-${resourceToken}'
var nsgName = 'nsg-hostalconnect-${resourceToken}'
var publicIpName = 'pip-hostalconnect-${resourceToken}'
var nicName = 'nic-hostalconnect-${resourceToken}'
var dnsLabel = 'hostalconnect-${resourceToken}'

resource networkSecurityGroup 'Microsoft.Network/networkSecurityGroups@2023-11-01' = {
  name: nsgName
  location: location
  tags: tags
  properties: {
    securityRules: [
      {
        name: 'Allow-SSH-From-Admin-IP'
        properties: {
          priority: 1000
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: sshSourceAddressPrefix
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '22'
        }
      }
      {
        name: 'Allow-HTTP'
        properties: {
          priority: 1010
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: 'Internet'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '80'
        }
      }
      {
        name: 'Allow-HTTPS'
        properties: {
          priority: 1020
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: 'Internet'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '443'
        }
      }
    ]
  }
}

resource virtualNetwork 'Microsoft.Network/virtualNetworks@2023-11-01' = {
  name: vnetName
  location: location
  tags: tags
  properties: {
    addressSpace: {
      addressPrefixes: [
        '10.42.0.0/16'
      ]
    }
    subnets: [
      {
        name: 'default'
        properties: {
          addressPrefix: '10.42.0.0/24'
          networkSecurityGroup: {
            id: networkSecurityGroup.id
          }
        }
      }
    ]
  }
}

resource publicIp 'Microsoft.Network/publicIPAddresses@2023-11-01' = {
  name: publicIpName
  location: location
  sku: {
    name: 'Standard'
  }
  tags: tags
  properties: {
    publicIPAllocationMethod: 'Static'
    publicIPAddressVersion: 'IPv4'
    dnsSettings: {
      domainNameLabel: dnsLabel
    }
  }
}

resource networkInterface 'Microsoft.Network/networkInterfaces@2023-11-01' = {
  name: nicName
  location: location
  tags: tags
  properties: {
    ipConfigurations: [
      {
        name: 'ipconfig1'
        properties: {
          privateIPAllocationMethod: 'Dynamic'
          subnet: {
            id: resourceId('Microsoft.Network/virtualNetworks/subnets', vnetName, 'default')
          }
          publicIPAddress: {
            id: publicIp.id
          }
        }
      }
    ]
  }
}

resource virtualMachine 'Microsoft.Compute/virtualMachines@2024-07-01' = {
  name: vmName
  location: location
  tags: union(tags, {
    workload: environmentName
  })
  properties: {
    hardwareProfile: {
      vmSize: 'Standard_D2s_v3'
    }
    osProfile: {
      computerName: 'hostalconnect'
      adminUsername: adminUsername
      customData: base64(loadTextContent('../cloud-init.yaml'))
      linuxConfiguration: {
        disablePasswordAuthentication: true
        ssh: {
          publicKeys: [
            {
              path: '/home/${adminUsername}/.ssh/authorized_keys'
              keyData: sshPublicKey
            }
          ]
        }
      }
    }
    storageProfile: {
      imageReference: {
        publisher: 'Canonical'
        offer: 'ubuntu-24_04-lts'
        sku: 'server'
        version: 'latest'
      }
      osDisk: {
        name: 'osdisk-hostalconnect-${resourceToken}'
        createOption: 'FromImage'
        diskSizeGB: 32
        managedDisk: {
          storageAccountType: 'StandardSSD_LRS'
        }
      }
    }
    networkProfile: {
      networkInterfaces: [
        {
          id: networkInterface.id
          properties: {
            primary: true
          }
        }
      ]
    }
  }
}

output vmName string = virtualMachine.name
output publicIpAddress string = publicIp.properties.ipAddress
output fqdn string = '${dnsLabel}.${location}.cloudapp.azure.com'
