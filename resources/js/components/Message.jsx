import React from "react";
import { FaCheckDouble, FaTrash } from "react-icons/fa";
import axios from "axios";

const Message = ({ rootUrl, user, message, chatId, setMessages, onNewMessageCount }) => {
    const deleteMessage = async (messageId) => {
        if (!messageId) return;
        try {
            const res = await axios.post(`${rootUrl}/chats/${chatId}/delete-message/${messageId}`);
            setMessages(res.data.messages);
        } catch (err) {
            console.error(err);
        }
    }
    const handleMessageDelete = async (messageId) => {
        if (window.confirm('Are you sure you want to delete this message?')) {
            await deleteMessage(messageId);
        } else {
            alert('Deletion cancelled')
        }
    }
    const isMine = message.user_id === user.id;
    return (
        <div className={`message ${isMine ? "sent" : "received"}`}>
            <div className="bubble">
                <span><b>{isMine ? 'You' : message.sender_name}</b></span><br />
                <span>{message.deleted_at ? 'This message has been deleted' : message.text}</span><br />
                {
                    !message.deleted_at ? (message.attachment != null ? <span><a href={"/storage/public/uploads/messages/" + message.attachment} target="_blank" className="btn btn-sm btn-outline-secondary me-2 mb-1"><i className="bi bi-paperclip"></i> Attachment</a></span> : '') : ''
                }
                {
                    isMine ? (!message.deleted_at ? <span style={{ cursor: "pointer" }} onClick={() => handleMessageDelete(message.id)}><FaTrash /></span> : '') : ''
                }
                <div className="meta">
                    <small>{new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</small>
                    {isMine ? (message.is_message_seen ? <FaCheckDouble className="tick" /> : '') : ''}
                </div>
            </div>
        </div>
    );
};

export default Message;
