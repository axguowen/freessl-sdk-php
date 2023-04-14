<?php
// +----------------------------------------------------------------------
// | FreeSSL SDK [FreeSSL SDK for PHP]
// +----------------------------------------------------------------------
// | FreeSSL SDK
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace axguowen;

use axguowen\freessl\BaseClient;

class FreeSSL extends BaseClient
{
    /**
     * 创建证书申请
     * @access public
     * @param string $domains 域名, 多个用英文逗号隔开
     * @param int $validityPeriod 证书有效期, 仅支持3和12
     * @param string $verifyMethod 验证方式 http, dns, email
     * @param string $email 邮箱
     * @return array
     */
    public function createCertificate($domains, $validityPeriod = 3, $verifyMethod = 'http', $email = '')
    {
        // 请求体
        $body = [
            'csr_pem' => $this->makeCSR($domains),
            'domains' => [$domains],
            'validity_period' => $validityPeriod,
            'verify_method' => $verifyMethod,
            'email' => $email,
        ];
        // 发送请求
        return $this->post('/certs', $body);
    }

    /**
     * 获取验证信息
     * @access public
     * @param string $id 证书ID/hash
     * @return array
     */
    public function getCertificateAuthInfo($id)
    {
        // 发送请求
        return $this->get('/certs/' . $id . '/auth-info');
    }

    /**
     * 验证域名
     * @access public
     * @param string $id 证书ID/hash
     * @return array
     */
    public function verifyDomain($id)
    {
        // 发送请求
        return $this->patch('/certs/' . $id);
    }

    /**
     * 下载证书
     * @access public
     * @param string $id 证书ID/hash
     * @return array
     */
    public function downloadCertificate($id)
    {
        // 通过请求接口获取授权请求头
        $headers = $this->getAuthHeader();
        // 构造请求路径
        $path = '/certs/' . $id . '/download';
        // 发送请求
        $ret = \axguowen\HttpClient::get(self::BASE_URL . $path, $headers);
        if (!$ret->ok()) {
            return [null, new \axguowen\httpclient\Error($path, $ret)];
        }
        // 发送请求
        return [
            [
                'code' => 0,
                'error' => '',
                'msg' => [
                    'certificate' => $ret->body,
                ],
            ],
            null
        ];
    }

    /**
     * 取消证书申请
     * @access public
     * @param string $id 证书ID/hash
     * @return array
     */
    public function cancelCertificate($id)
    {
        // 发送请求
        return $this->delete('/certs/' . $id . '/cancel');
    }

    /**
     * 吊销证书
     * @access public
     * @param string $id 证书ID/hash
     * @return array
     */
    public function revokeCertificate($id)
    {
        // 发送请求
        return $this->delete('/certs/' . $id . '/revoke');
    }

    /**
     * 获取证书列表
     * @access public
     * @param array $options 请求参数
     * @return array
     */
    public function getCertificateList($options = [])
    {
        $query = [
            'page' => 1,
            'per_page' => 10,
            'status' => 'draft',
        ];

        if(!empty($options)){
            $query = array_merge($query, $options);
        }
        // 发送请求
        return $this->get('/certs', $query);
    }

    /**
     * 获取验证邮箱
     * @access public
     * @param string $domain 域名
     * @return array
     */
    public function getApproverEmail($domain)
    {
        $query = [
            'domain' => $domain,
        ];
        // 发送请求
        return $this->get('/verify-email', $query);
    }

    /**
     * 获取CSR
     * @access public
     * @param string $domain 域名
     * @return string
     */
    public function generateCSR($domain)
    {
        // 参数
        $dn = [
            'common_name' => $domain
        ];
        // 指定所在国家
        if(!empty($this->options['country_name'])){
            $dn['country'] = $this->options['country_name'];
        }
        // 指定所在省份
        if(!empty($this->options['state_or_province_name'])){
            $dn['state'] = $this->options['state_or_province_name'];
        }
        // 指定所在城市
        if(!empty($this->options['locality_name'])){
            $dn['locality'] = $this->options['locality_name'];
        }
        // 指定注册人姓名
        if(!empty($this->options['organization_name'])){
            $dn['organization'] = $this->options['organization_name'];
        }
        // 指定组织名称
        if(!empty($this->options['organizational_unit_name'])){
            $dn['organizational_unit'] = $this->options['organizational_unit_name'];
        }
        // 生成CSR
        $generateCSR = $this->get('/gen-csr', $dn);
        // 如果返回错误
        if(is_null($generateCSR[0])){
            $error = $generateCSR[0];
            throw new \Exception($error->message());
        }
        // 存储私钥
        $this->privateKey = $generateCSR[0]['msg']['private_key'];
        // 发送请求
        return $generateCSR[0]['msg']['csr'];
    }
}
